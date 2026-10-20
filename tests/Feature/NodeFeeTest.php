<?php

use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Models\Withdraw;
use App\Services\NodeFeeService;
use App\Services\WithdrawOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeFeeUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    // 充值 NOX，保证 createOrder 冻结时有可用余额
    WalletLog::addLog($user->id, '5000.000000', WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);

    return $user;
}

function makeDoneWithdraw(User $user, string $amount, string $fee, string $fee_rate = '5.00', string $symbol = 'NOX'): Withdraw
{
    $order = WithdrawOrderService::createOrder($user, [
        'symbol' => $symbol,
        'amount' => $amount,
        'real_am' => bcsub($amount, $fee, 6),
        'fee' => $fee,
        'fee_rate' => $fee_rate,
        'withdraw_mode' => Withdraw::WITHDRAW_MODE_CONTRACT,
    ], [
        'sign' => '0x' . str_repeat('f', 64),
        'message' => 'msg',
        'project_signer' => '0x' . str_repeat('e', 40),
        'withdraw_contract' => '0x' . str_repeat('d', 40),
        'deadline' => now()->addMinutes(5),
    ]);

    return $order;
}

test('doneCallback 将手续费累计到当日手续费表', function () {
    $user = makeFeeUser();
    $order = makeDoneWithdraw($user, '1000.000000', '50.000000');

    WithdrawOrderService::doneCallback($order, '0x' . str_repeat('a', 64));

    expect(Withdraw::find($order->id)->status)->toBe(Withdraw::STATUS_DONE);
    $feeRow = DB::table('fees')->where('date', now()->toDateString())->first();
    expect(bcadd((string) $feeRow->amount, '0', 6))->toBe('50.000000');
    expect(DB::table('fee_logs')->whereDate('created_at', now()->toDateString())->count())->toBe(1);
});

test('doneCallback 同日多笔提现手续费累计', function () {
    $user = makeFeeUser();
    $o1 = makeDoneWithdraw($user, '1000.000000', '50.000000');
    $o2 = makeDoneWithdraw($user, '2000.000000', '100.000000');

    WithdrawOrderService::doneCallback($o1, '0x' . str_repeat('a', 64));
    WithdrawOrderService::doneCallback($o2, '0x' . str_repeat('b', 64));

    $feeRow = DB::table('fees')->where('date', now()->toDateString())->first();
    expect(bcadd((string) $feeRow->amount, '0', 6))->toBe('150.000000');
    expect(DB::table('fee_logs')->whereDate('created_at', now()->toDateString())->count())->toBe(2);
});

test('doneCallback 手续费为零时不写手续费行', function () {
    $user = makeFeeUser();
    $order = makeDoneWithdraw($user, '1000.000000', '0.000000');

    WithdrawOrderService::doneCallback($order, '0x' . str_repeat('a', 64));

    expect(DB::table('fees')->where('date', now()->toDateString())->count())->toBe(0);
    expect(DB::table('fee_logs')->whereDate('created_at', now()->toDateString())->count())->toBe(0);
});

test('doneCallback 不将 USDT 手续费计入手续费表', function () {
    $user = makeFeeUser();
    // 给 USDT 余额，保证 USDT 提现冻结时有可用余额
    WalletLog::addLog($user->id, '5000.000000', WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    $order = makeDoneWithdraw($user, '1000.000000', '50.000000', '5.00', WalletLog::SYMBOL_USDT);

    WithdrawOrderService::doneCallback($order, '0x' . str_repeat('a', 64));

    expect(Withdraw::find($order->id)->status)->toBe(Withdraw::STATUS_DONE);
    // USDT 手续费不进 NOX 节点分红池
    expect(DB::table('fees')->where('date', now()->toDateString())->count())->toBe(0);
    expect(DB::table('fee_logs')->whereDate('created_at', now()->toDateString())->count())->toBe(0);
});

function makeNodeFeeUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);

    return $user;
}

function seedFee(string $amount, ?string $date = null): void
{
    DB::table('fees')->insert([
        'date' => $date ?? now()->toDateString(),
        'amount' => $amount,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('节点池分红按 node_num 权重分发给持节点用户', function () {
    $u1 = makeNodeFeeUser();
    $u2 = makeNodeFeeUser();
    makeNodeOrder($u1);
    makeNodeOrder($u2);
    seedFee('100.000000');

    $ok = (new NodeFeeService(false))->run(now()->toDateString());

    expect($ok)->toBeTrue();
    $alloc = NodeFeeAllocation::query()->where('sett_date', now()->toDateString())->first();
    expect($alloc->node_fee_am)->toBe('90.000000'); // 100 × 90%
    expect($alloc->node_count)->toBe(2); // 全局节点总数 = Σ node_num
    expect($alloc->per_share)->toBe('45.000000'); // 每节点份额
    expect($alloc->project_am)->toBe('0.000000'); // 项目钱包机制已取消
    expect($alloc->remain)->toBe('10.000000'); // 10% 留在 remain
    expect(UserData::getInfo($u1->id)->current_nox)->toBe('45.000000');
    expect(UserData::getInfo($u2->id)->current_nox)->toBe('45.000000');
    expect(NodeFeeLog::count())->toBe(2);
    $log = NodeFeeLog::query()->where('user_id', $u1->id)->first();
    expect($log->am)->toBe('45.000000');
    expect($log->node_num)->toBe(1);
    expect($log->total_num)->toBe(2);
    expect($log->total_am)->toBe('90.000000');
    expect(WalletLog::query()->where('type', WalletLog::TYPE_NODE_FEE_PROJECT)->exists())->toBeFalse();
});

test('一笔多节点订单按 node_num 计份而非订单条数', function () {
    $u1 = makeNodeFeeUser();
    $u2 = makeNodeFeeUser();
    makeNodeOrder($u1, num: 3); // 一笔订单 3 个节点 → node_num=3
    makeNodeOrder($u2);         // node_num=1
    seedFee('100.000000');

    (new NodeFeeService(false))->run(now()->toDateString());

    $alloc = NodeFeeAllocation::query()->where('sett_date', now()->toDateString())->first();
    expect($alloc->node_count)->toBe(4);
    expect($alloc->per_share)->toBe('22.500000');
    expect(UserData::getInfo($u1->id)->current_nox)->toBe('67.500000'); // 22.5 × 3
    expect(UserData::getInfo($u2->id)->current_nox)->toBe('22.500000');
    expect(NodeFeeLog::count())->toBe(2); // 每持节点用户一行
    $l1 = NodeFeeLog::query()->where('user_id', $u1->id)->first();
    expect($l1->am)->toBe('67.500000');
    expect($l1->node_num)->toBe(3);
    expect($l1->total_num)->toBe(4);
});

test('无持节点用户时分红池全部留在 remain', function () {
    seedFee('100.000000');

    (new NodeFeeService(false))->run(now()->toDateString());

    $alloc = NodeFeeAllocation::query()->where('sett_date', now()->toDateString())->first();
    expect($alloc->node_fee_am)->toBe('90.000000');
    expect($alloc->node_count)->toBe(0);
    expect($alloc->per_share)->toBe('0.000000');
    expect($alloc->remain)->toBe('100.000000'); // 90 + 10，均未分配
    expect($alloc->status)->toBe(NodeFeeAllocation::STATUS_DONE);
    expect(NodeFeeLog::count())->toBe(0);
});

test('分红取整尾差与 10% 一并计入 remain', function () {
    $u1 = makeNodeFeeUser();
    $u2 = makeNodeFeeUser();
    makeNodeOrder($u1, num: 4);
    makeNodeOrder($u2, num: 3); // 总权重 7，pool 90 无法整除 → 出现取整尾差
    seedFee('100.000000');

    $ok = (new NodeFeeService(false))->run(now()->toDateString());

    expect($ok)->toBeTrue();
    $alloc = NodeFeeAllocation::query()->where('sett_date', now()->toDateString())->first();
    expect($alloc->node_fee_am)->toBe('90.000000'); // 100 × 90%
    expect($alloc->node_count)->toBe(7);
    expect($alloc->per_share)->toBe('12.857142'); // floor(90 ÷ 7, 6)
    expect(UserData::getInfo($u1->id)->current_nox)->toBe('51.428568'); // 12.857142 × 4
    expect(UserData::getInfo($u2->id)->current_nox)->toBe('38.571426'); // 12.857142 × 3
    expect($alloc->remain)->toBe('10.000006'); // 100 − (51.428568 + 38.571426)，10% + 尾差 0.000006
});

test('节点池分红结算幂等且不重复入账', function () {
    $u1 = makeNodeFeeUser();
    makeNodeOrder($u1);
    seedFee('100.000000');

    (new NodeFeeService(false))->run(now()->toDateString());
    (new NodeFeeService(false))->run(now()->toDateString());

    expect(NodeFeeAllocation::query()->where('sett_date', now()->toDateString())->count())->toBe(1);
    expect(NodeFeeLog::count())->toBe(1);
    expect(UserData::getInfo($u1->id)->current_nox)->toBe('90.000000');
});

test('恢复重跑不会对已入账持节点用户重复分发', function () {
    $u1 = makeNodeFeeUser();
    $order = makeNodeOrder($u1);
    seedFee('100.000000');

    // 构造 RUNNING allocation（上次未完成结算残留），持节点用户已入账并落一行
    $alloc = NodeFeeAllocation::create([
        'sett_date' => now()->toDateString(),
        'total_fee' => '100.000000',
        'node_fee_am' => '90.000000',
        'node_count' => 1,
        'per_share' => '90.000000',
        'project_am' => '0.000000',
        'remain' => '0.000000',
        'status' => NodeFeeAllocation::STATUS_RUNNING,
    ]);
    WalletLog::addLog($u1->id, '90.000000', WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_NODE_FEE_IN);
    NodeFeeLog::create([
        'sett_date' => now()->toDateString(),
        'alloc_id' => $alloc->id,
        'user_id' => $u1->id,
        'am' => '90.000000',
        'node_num' => 1,
        'total_num' => 1,
        'total_am' => '90.000000',
    ]);

    $ok = (new NodeFeeService(false))->run(now()->toDateString());

    expect($ok)->toBeTrue();
    expect(UserData::getInfo($u1->id)->current_nox)->toBe('90.000000'); // 不重复入账
    expect(NodeFeeLog::count())->toBe(1);
    $fresh = NodeFeeAllocation::find($alloc->id);
    expect($fresh->status)->toBe(NodeFeeAllocation::STATUS_DONE);
    expect($fresh->remain)->toBe('10.000000'); // fee − 已发(90)，10% 留在 remain
});
