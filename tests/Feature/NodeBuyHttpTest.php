<?php

use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// 建一个初始 V1 的节点买家（自引用闭包行：run 会 check 用户自己）
function makeNodeBuyer(): array
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]);
    (new \App\Services\UserRelationService)->setupUserRelations($user->id, 0);
    UserData::addInfo($user->id);
    $product = NodeProduct::create([
        'name' => '算力加速节点',
        'price' => '1000.000000',
        'status' => NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);

    return [$user, $product];
}

function makeNodePay(User $user): array
{
    return [
        'pay_contract' => '0x'.str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'dest_addr' => '0x'.str_repeat('c', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
}

beforeEach(function () {
    config(['site.pay_contract' => '0x'.str_repeat('a', 40)]);
    config(['app.check.payOrder' => true]);
    seedChainAndAllAsset();
    Cache::flush();
    addGlobalConfig('site_allow_node_buy', 1, 'node');
});

test('node/create 返回支付信息且不扣内部余额', function () {
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    Sanctum::actingAs($user);
    NodeProduct::create([
        'name' => '算力加速节点',
        'price' => '1000.000000',
        'status' => NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);

    // 节点购买额度 = floor(curr_stake / 5000)：先让用户持有算力质押，才有足够购买额度
    UserData::query()->where('user_id', $user->id)->update([
        'curr_stake' => '10000.000000', // 额度 = 2，可买 1 个节点
    ]);

    $resp = $this->postJson('/api/node/create', ['num' => 1]);

    $resp->assertJson(['code' => 200]);
    $order = NodeOrder::first();
    expect($order->status)->toBe(NodeOrder::STATUS_WAIT);
    expect(UserData::getInfo($user->id)->current_usdt)->toBe('0.000000');
    expect($resp->json('data.sn'))->toBe($order->sn);
});

test('node/create 拒绝已下架的节点产品', function () {
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    Sanctum::actingAs($user);
    $product = NodeProduct::create([
        'name' => '下架节点',
        'price' => '1000.000000',
        'status' => NodeProduct::STATUS_OFF,
        'sort' => 1,
    ]);

    $resp = $this->postJson('/api/node/create', ['product_id' => $product->id]);

    $resp->assertJson(['code' => 400]);
    expect(NodeOrder::count())->toBe(0);
});

test('node/done 支付成功后持节点用户升至 V3 并记录等级历史', function () {
    config(['app.check.pay' => false]); // 链上支付走 mock，视为已支付
    config(['queue.default' => 'sync']); // UserUpgradeJob 同步执行，端到端验证

    [$user, $product] = makeNodeBuyer();
    Sanctum::actingAs($user);
    $order = \App\Services\NodeBuyService::createOrder($user, $product, 1, makeNodePay($user));

    $resp = $this->postJson('/api/node/done', ['sn' => $order->sn, 'hash' => '0x'.str_repeat('f', 64)]);

    $resp->assertJson(['code' => 200]);
    expect(UserData::getInfo($user->id)->node_id)->toBe($product->id);
    // done 派发 UserUpgradeJob → run(self) 命中持节点保底 V3
    expect(User::find($user->id)->level_id)->toBe(3);

    $history = \App\Models\LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('持有节点');
});

test('node 由 app:order-pay 对账确认后升至 V3 并记录等级历史', function () {
    config(['app.check.pay' => false]); // getPayInfoFromContract mock：视为已支付
    config(['app.check.payOrder' => true]);
    config(['queue.default' => 'sync']);

    [$user, $product] = makeNodeBuyer();
    $order = \App\Services\NodeBuyService::createOrder($user, $product, 1, makeNodePay($user));
    // app:order-pay 只扫 [now-24h, now-1min]，把支付单挪进扫描窗口
    \App\Models\PayOrder::query()->where('sn', $order->sn)->update(['created_at' => now()->subMinutes(2)]);

    config(['app.check.payOrder' => false]);
    $this->artisan('app:order-pay')->assertExitCode(0);

    expect($order->refresh()->status)->toBe(NodeOrder::STATUS_PAID);
    expect(UserData::getInfo($user->id)->node_id)->toBe($product->id);
    // 对账确认同样触发 UserUpgradeJob → 持有节点保底 V3
    expect(User::find($user->id)->level_id)->toBe(3);

    $history = \App\Models\LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('持有节点');
});

test('node 已被确认后再调 done（已支付早退）仍补触发升至 V3', function () {
    config(['app.check.pay' => false]);
    config(['queue.default' => 'sync']);

    [$user, $product] = makeNodeBuyer();
    Sanctum::actingAs($user);
    $order = \App\Services\NodeBuyService::createOrder($user, $product, 1, makeNodePay($user));

    // 模拟 cron/旧实现已把订单确认成 PAID（写 node_id 但不派发升级任务），用户仍是 V1
    $payOrder = \App\Models\PayOrder::query()->where('sn', $order->sn)->first();
    \App\Services\NodeBuyService::payCallback($order, $payOrder, '0x'.str_repeat('f', 64), $order->amount);
    expect(User::find($user->id)->level_id)->toBe(1);

    // done 走“已支付”早退分支，也应补触发等级检查
    $resp = $this->postJson('/api/node/done', ['sn' => $order->sn, 'hash' => '0x'.str_repeat('f', 64)]);
    $resp->assertJson(['code' => 200]);

    expect(UserData::getInfo($user->id)->node_id)->toBe($product->id);
    expect(User::find($user->id)->level_id)->toBe(3);

    $history = \App\Models\LevelHistory::where('user_id', $user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_level)->toBe(1)
        ->and($history->new_level)->toBe(3)
        ->and($history->remark)->toContain('持有节点');
});
