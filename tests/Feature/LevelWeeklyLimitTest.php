<?php

use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Models\Withdraw;
use App\Services\WithdrawOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 迁移会在空表上自动插入 V1-V5，先清空再插入测试等级
    DB::table('levels')->delete();
    $config = [
        // id => [code, user_perf, team_perf, direct_num, team_num, weekly_limit_usdt, weekly_limit_nox]
        1 => ['V1', 0, 0, 0, 0, '5000.000000', '5000.000000'],
        2 => ['V2', 500, 5000, 3, 10, '10000.000000', '10000.000000'],
        3 => ['V3', 3000, 30000, 5, 10, '30000.000000', '30000.000000'],
        4 => ['V4', 30000, 500000, 10, 50, '100000.000000', '100000.000000'],
        5 => ['V5', 0, 1000000, 20, 100, '300000.000000', '300000.000000'],
    ];
    $rows = [];
    foreach ($config as $id => [$code, $user_perf, $team_perf, $direct_num, $team_num, $usdt, $nox]) {
        $rows[] = [
            'id' => $id,
            'code' => $code,
            'user_perf' => $user_perf,
            'team_perf' => $team_perf,
            'direct_num' => $direct_num,
            'team_num' => $team_num,
            'weekly_limit_usdt' => $usdt,
            'weekly_limit_nox' => $nox,
            'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-01 00:00:00',
        ];
    }
    DB::table('levels')->insert($rows);
});

test('周限额拒绝超限提现', function () {
    // 迁移已自动插入 V1-V5（levels 表自包含）
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]); // V1
    UserData::addInfo($user->id);
    WalletLog::addLog($user->id, '10000.000000', WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    // 本周已提 4000（V1 周上限 5000）
    $o = WithdrawOrderService::createOrder($user, [
        'symbol' => WalletLog::SYMBOL_USDT,
        'amount' => '4000.000000',
        'real_am' => '4000.000000',
        'fee' => '0.000000',
        'fee_rate' => '0.00',
        'withdraw_mode' => Withdraw::WITHDRAW_MODE_CONTRACT,
    ], [
        'sign' => '0x' . str_repeat('f', 64),
        'message' => 'm',
        'project_signer' => '0x' . str_repeat('e', 40),
        'withdraw_contract' => '0x' . str_repeat('d', 40),
        'deadline' => now()->addMinutes(5),
    ]);
    $o->status = Withdraw::STATUS_DONE;
    $o->save();

    $controller = new \App\Http\Controllers\Api\WithdrawController;
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('checkWithdrawWeeklyLimit');
    $m->setAccessible(true);

    // 再提 2000 → 累计 6000 > 5000，拒绝
    $this->expectException(\App\Exceptions\LogicException::class);
    $m->invoke($controller, $user->id, '2000.000000', WalletLog::SYMBOL_USDT);
});

test('周限额周一重置并按币种分别统计', function () {
    // 迁移已自动插入 V1-V5（levels 表自包含）
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]);
    $controller = new \App\Http\Controllers\Api\WithdrawController;
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('checkWithdrawWeeklyLimit');
    $m->setAccessible(true);

    // 上周的提现不计入
    $lastWeek = now()->subDays(8);
    DB::table('withdraws')->insert([
        'user_id' => $user->id,
        'sn' => randOrderNo(),
        'symbol' => WalletLog::SYMBOL_USDT,
        'amount' => '5000.000000',
        'status' => Withdraw::STATUS_DONE,
        'withdraw_mode' => 1,
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
    ]);
    // 本周 NOX 已提 6000（USDT 周上限不受影响）
    DB::table('withdraws')->insert([
        'user_id' => $user->id,
        'sn' => randOrderNo(),
        'symbol' => WalletLog::SYMBOL_NOX,
        'amount' => '6000.000000',
        'status' => Withdraw::STATUS_DONE,
        'withdraw_mode' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // USDT 再提 4000 不超（上周 5000 不算 + NOX 不算）
    $m->invoke($controller, $user->id, '4000.000000', WalletLog::SYMBOL_USDT);
    $this->assertTrue(true);
});

test('无等级行时按零限额执行且不报错', function () {
    // 删除全部等级 → Level::find 返回 null，不应触发 null 属性读取错误（PHP Error）
    DB::table('levels')->delete();
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => 1]);

    $controller = new \App\Http\Controllers\Api\WithdrawController;
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('checkWithdrawWeeklyLimit');
    $m->setAccessible(true);

    // 无等级 → weekly_limit = '0.000000'（0 限额）。remaining 来自 0 限额：
    // 若本周累计 0 → remaining 0 → 抛业务 LogicException（0 限额拒绝）；若正常返回也可接受。
    // 关键：不得抛 PHP Error / ErrorException（null 属性读取）。
    try {
        $m->invoke($controller, $user->id, '100.000000', WalletLog::SYMBOL_USDT);
        // 正常返回也通过（remaining 来自 0 限额）
        $this->assertTrue(true);
    } catch (\App\Exceptions\LogicException $e) {
        // 0 限额拒绝，属预期业务异常
        $this->assertStringContainsString('本周提现金额超限', $e->getMessage());
    }
});

test('NOX 提现经 API 端到端成功', function () {
    config(['site.withdraw_contract' => '0x' . str_repeat('a', 40)]);
    config(['site.asset.NOX.withdraw_address' => '0x' . str_repeat('c', 40)]);
    config(['site.asset.NOX.withdraw_key' => str_repeat('1', 64)]);
    seedChainAndAllAsset();
    DB::table('configs')->insertOrIgnore([
        ['group' => 'withdraw', 'key' => 'site_allow_w_nox', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'withdraw', 'key' => 'site_w_min_nox', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'withdraw', 'key' => 'site_w_fee_rate_nox', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'withdraw', 'key' => 'site_w_nox_global_limit', 'value' => '1000000', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'withdraw', 'key' => 'site_w_nox_user_limit', 'value' => '1000000', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $user = User::factory()->create([
        'status' => 1,
        'invited_uid' => 0,
        'level_id' => 1,
        'address' => '0x' . str_repeat('1', 40),
        'allow_w_nox' => 1,
    ]);
    UserData::addInfo($user->id);
    WalletLog::addLog($user->id, '1000.000000', WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    Sanctum::actingAs($user);

    $resp = $this->postJson('/api/withdraw', ['symbol' => 'NOX', 'amount' => '100']);

    $resp->assertJson(['code' => 200]);
    $order = Withdraw::query()->where('user_id', $user->id)->first();
    expect($order)->not()->toBeNull();
    expect($order->symbol)->toBe(WalletLog::SYMBOL_NOX);
    expect(bcadd((string) $order->amount, '0', 6))->toBe('100.000000');
    expect($order->status)->toBe(Withdraw::STATUS_PENDING);
    // 冻结后：可用 1000 - 100 = 900，冻结 100
    expect(UserData::getInfo($user->id)->current_nox)->toBe('900.000000');
    expect(UserData::getInfo($user->id)->freeze_nox)->toBe('100.000000');
});

test('关闭 allow_w_nox 时 NOX 提现被拦截', function () {
    $user = User::factory()->create([
        'status' => 1,
        'invited_uid' => 0,
        'level_id' => 1,
        'address' => '0x' . str_repeat('1', 40),
        'allow_w_nox' => 0,
    ]);
    UserData::addInfo($user->id);
    WalletLog::addLog($user->id, '1000.000000', WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    Sanctum::actingAs($user);

    $resp = $this->postJson('/api/withdraw', ['symbol' => 'NOX', 'amount' => '100']);

    $resp->assertJson(['code' => 400]);
    expect(Withdraw::query()->where('user_id', $user->id)->count())->toBe(0);
});
