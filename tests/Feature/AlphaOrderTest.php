<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\AlphaReward;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('资产包常量与模型已注册', function () {
    expect(PayOrder::TYPE_ALPHA_BUY)->toBe(2);
    expect(WalletLog::TYPE_ALPHA_REWARD_IN)->toBe(130);
    expect(WalletLog::TYPE_ALPHA_EXIT)->toBe(131);
    expect(WalletLog::TYPE_ALPHA_UNFREEZE)->toBe(132);

    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    expect(AlphaProduct::find($product->id)->tier)->toBe('basic');

    $order = AlphaOrder::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'power_am' => '1000.000000',
        'token_price' => '1.00000000',
        'reward_rate' => '17.00',
        'quota_reward' => '2500.000000',
        'rem_reward' => '2500.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);
    expect(AlphaOrder::getBySn($order->sn)->id)->toBe($order->id);
    expect(AlphaOrder::STATUS_MAP[AlphaOrder::STATUS_PAID])->toBe('进行中');

    AlphaReward::create([
        'user_id' => 1,
        'order_id' => $order->id,
        'order_am' => '1000.000000',
        'rate' => '17.00',
        'reward_am' => '5.66666666',
        'settle_date' => now()->toDateString(),
    ]);
    expect(AlphaReward::count())->toBe(1);
});

test('用户资金流水类型映射包含资产包类型', function () {
    $map = WalletLog::getTypeMapUser();
    expect($map)->toHaveKey(WalletLog::TYPE_ALPHA_REWARD_IN, '资产包每日产出')
        ->toHaveKey(WalletLog::TYPE_ALPHA_EXIT, '资产包首月退出退款')
        ->toHaveKey(WalletLog::TYPE_ALPHA_UNFREEZE, '冻结收益解冻');

    $usdtFilter = WalletLog::getTypeMapUserFilter(WalletLog::SYMBOL_USDT);
    expect($usdtFilter)->toHaveKey(WalletLog::TYPE_ALPHA_REWARD_IN, '资产包每日产出')
        ->toHaveKey(WalletLog::TYPE_ALPHA_EXIT, '资产包首月退出退款')
        ->toHaveKey(WalletLog::TYPE_ALPHA_UNFREEZE, '冻结收益解冻');

    // 资产包变动均为 USDT，NOX 筛选不应出现
    $noxFilter = WalletLog::getTypeMapUserFilter(WalletLog::SYMBOL_NOX);
    expect($noxFilter)->not()->toHaveKey(WalletLog::TYPE_ALPHA_REWARD_IN);
});

test('createOrder 构建资产包订单与支付单（含快照）', function () {
    config(['site.pay_contract' => '0x'.str_repeat('a', 40)]);
    seedChainAndAllAsset();

    $user = makeAlphaUser();
    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $pay = [
        'pay_contract' => config('site.pay_contract'),
        'pay_addr' => $user->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'token_price' => '1.00000000',
        'dest_addr' => '0x'.str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];

    $order = AlphaOrderService::createOrder($user, $product, '1000.000000', $pay);

    expect($order->amount)->toBe('1000.000000');
    expect($order->power_am)->toBe('1000.000000');
    expect($order->reward_rate)->toBe('17.00');
    expect($order->quota_reward)->toBe('2500.000000');
    expect($order->rem_reward)->toBe('2500.000000');
    expect($order->status)->toBe(AlphaOrder::STATUS_WAIT);
    expect($order->trial_end->gt(now()))->toBeTrue();
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    expect($payOrder->type)->toBe(PayOrder::TYPE_ALPHA_BUY);
    expect($payOrder->order_id)->toBe($order->id);
});

test('payCallback 激活订单并触发解冻', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S=25000
    \App\Services\AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    \App\Services\AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    expect(UserData::getInfo($user->id)->alpha_frozen)->toBe('100.000000');

    // 用户再申购一笔，直接走支付回调
    $product = AlphaProduct::first();
    $pay = [
        'pay_contract' => '0x'.str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'token_price' => '0.10000000',
        'dest_addr' => '0x'.str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = AlphaOrderService::createOrder($user, $product, '10000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    AlphaOrderService::payCallback($order, $payOrder, '0x'.str_repeat('f', 64), '10000.000000');

    expect(AlphaOrder::find($order->id)->status)->toBe(AlphaOrder::STATUS_PAID);
    $data = UserData::getInfo($user->id);
    expect($data->alpha_frozen)->toBe('0.000000');
    expect($data->current_slot)->toBe('110000.000000'); // 增加额度槽
});

test('首月试用期退出：退 80% 本金并扣除质押/额度槽/算力/业绩', function () {
    $user = makeAlphaUser();
    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $pay = [
        'pay_contract' => config('site.pay_contract'),
        'pay_addr' => $user->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'token_price' => '1.00000000',
        'dest_addr' => '0x'.str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    // 真实申购：回调会把质押/额度槽/算力/业绩入账
    $order = AlphaOrderService::createOrder($user, $product, '1000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    AlphaOrderService::payCallback($order, $payOrder, '0x'.str_repeat('f', 64), '1000.000000');

    $data = UserData::getInfo($user->id);
    expect($data->curr_stake)->toBe('1000.000000');
    expect($data->current_slot)->toBe('1000.000000');
    expect($data->perf)->toBe('1000.000000');
    expect($data->curr_power)->toBe('1000.000000');

    AlphaOrderService::exit(AlphaOrder::find($order->id));

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->status)->toBe(AlphaOrder::STATUS_EXITED);
    expect($fresh->exit_at)->not()->toBeNull();
    $after = UserData::getInfo($user->id);
    expect($after->current_usdt)->toBe('800.000000');  // 80% 退款
    expect($after->curr_stake)->toBe('0.000000');      // 扣除质押量
    expect($after->current_slot)->toBe('0.000000');    // 扣除额度槽
    expect($after->perf)->toBe('0.000000');            // 扣除业绩
    expect($after->curr_power)->toBe('0.000000');      // 扣除算力
    expect(WalletLog::query()->where('user_id', $user->id)->where('type', WalletLog::TYPE_ALPHA_EXIT)->count())->toBe(1);
});

beforeEach(function () {
    config(['site.pay_contract' => '0x'.str_repeat('a', 40)]);
    // 本地 .env 将 APP_CHECK_PAY_ORDER 置为 false；此处显式恢复默认 true，保证 power/create 走 WAIT 分支
    config(['app.check.payOrder' => true]);
    seedChainAndAllAsset();
    Cache::flush();
});

test('power/create 返回支付信息且不扣内部余额', function () {
    $user = makeAlphaUser();
    Sanctum::actingAs($user);
    AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);

    addGlobalConfig('site_allow_power_buy', 1, 'power');
    $resp = $this->postJson('/api/power/create', ['product_id' => 1, 'amount' => '1000']);

    $resp->assertJson(['code' => 200]);
    $order = AlphaOrder::first();
    expect($order->status)->toBe(AlphaOrder::STATUS_WAIT);
    expect(UserData::getInfo($user->id)->current_usdt)->toBe('0.000000'); // 不扣余额
    expect($resp->json('data.sn'))->toBe($order->sn);
});

test('power/create 拒绝超出产品区间金额', function () {
    $user = makeAlphaUser();
    Sanctum::actingAs($user);
    AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);

    $resp = $this->postJson('/api/power/create', ['product_id' => 1, 'amount' => '50']);

    $resp->assertJson(['code' => 400]);
    expect(AlphaOrder::count())->toBe(0);
});

test('order-pay 完成资产包支付单', function () {
    $user = makeAlphaUser();
    config(['app.check.payOrder' => false]);
    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $pay = [
        'pay_contract' => '0x'.str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'token_price' => '1.00000000',
        'dest_addr' => '0x'.str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = AlphaOrderService::createOrder($user, $product, '1000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    $cmd = new \App\Console\Commands\OrderPayCmd();
    $cmd->setOutput(new \Illuminate\Console\OutputStyle(
        new \Symfony\Component\Console\Input\ArrayInput([]),
        new \Symfony\Component\Console\Output\BufferedOutput
    ));
    $result = $cmd->processOrder($payOrder);

    expect($result)->toBe('success');
    expect(AlphaOrder::find($order->id)->status)->toBe(AlphaOrder::STATUS_PAID);
});
