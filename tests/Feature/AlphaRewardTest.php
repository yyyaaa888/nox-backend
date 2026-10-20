<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\AlphaReward;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaQuotaService;
use App\Services\AlphaRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creditEarnings 槽位内收益全额入可用', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000

    $res = AlphaQuotaService::creditEarnings($user->id, '100.000000', WalletLog::TYPE_ALPHA_REWARD_IN);

    expect($res['available'])->toBe('100.000000');
    expect($res['frozen'])->toBe('0.000000');
    $data = UserData::getInfo($user->id);
    expect($data->current_nox)->toBe('100.000000');
    expect($data->alpha_quota_used)->toBe('100.000000');
    expect($data->alpha_frozen)->toBe('0.000000');
});

test('creditEarnings 超出槽位部分进入冻结', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000
    AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN); // C = 24900

    $res = AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);

    expect($res['available'])->toBe('100.000000'); // 25000-24900
    expect($res['frozen'])->toBe('100.000000');    // 200-100
    $data = UserData::getInfo($user->id);
    expect($data->alpha_quota_used)->toBe('25000.000000'); // 封顶到 S
    expect($data->alpha_frozen)->toBe('100.000000');
});

test('sweep 新订单后解冻冻结收益', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000
    AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    expect(UserData::getInfo($user->id)->alpha_frozen)->toBe('100.000000');

    makeAlphaOrder($user, '10000.000000'); // 新订单 → S = 50000

    $swept = AlphaQuotaService::sweep($user->id);

    expect($swept)->toBe('100.000000');
    $data = UserData::getInfo($user->id);
    expect($data->alpha_frozen)->toBe('0.000000');
    expect($data->current_nox)->toBe('25100.000000'); // 24900 + 100(入账) + 100(解冻)
    // 放行的收益须占用槽位，否则同一容量会被重复使用
    expect($data->alpha_quota_used)->toBe('25100.000000'); // 25000(已占用) + 100(本次放行)
});

test('sweep 放行收益占用额度槽（不产生重复容量）', function () {
    $user = makeAlphaUser();
    makeAlphaOrder($user, '10000.000000'); // S = 25000
    AlphaQuotaService::creditEarnings($user->id, '24900.000000', WalletLog::TYPE_ALPHA_REWARD_IN); // C=24900
    AlphaQuotaService::creditEarnings($user->id, '200.000000', WalletLog::TYPE_ALPHA_REWARD_IN);   // C=25000, F=100

    makeAlphaOrder($user, '10000.000000'); // 新订单 → S = 50000
    AlphaQuotaService::sweep($user->id);   // 放行 100 → C 应同步 +100

    $data = UserData::getInfo($user->id);
    expect($data->alpha_quota_used)->toBe('25100.000000');
    expect($data->alpha_frozen)->toBe('0.000000');

    // 再入账 25000：正确场景只能再放行 24900（50000-25100），累计可用封顶 2.5× = 50000
    $res = AlphaQuotaService::creditEarnings($user->id, '25000.000000', WalletLog::TYPE_ALPHA_REWARD_IN);
    expect($res['available'])->toBe('24900.000000');
    expect($res['frozen'])->toBe('100.000000');
    expect(UserData::getInfo($user->id)->current_nox)->toBe('50000.000000'); // 25100 + 24900
});

test('每日结算入账收益并扣减剩余可产出', function () {
    seedChainAndAllAsset();
    $user = makeAlphaUser();
    $order = makeAlphaOrder($user, '10000.000000', '17.00'); // 日产出 = 10000*17/3000 = 56.66666666

    $ok = (new AlphaRewardService(false))->run(now()->toDateString());

    expect($ok)->toBeTrue();
    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('56.666667');
    expect($fresh->rem_reward)->toBe('24943.333333'); // 25000 - 56.66666666
    expect(UserData::getInfo($user->id)->current_nox)->toBe('56.666667');
    expect(UserData::getInfo($user->id)->stake_reward)->toBe('56.666667'); // 累计收益
    expect(AlphaReward::query()->where('order_id', $order->id)->where('settle_date', now()->toDateString())->count())->toBe(1);
});

test('同一订单同一天每日结算幂等', function () {
    seedChainAndAllAsset();
    $user = makeAlphaUser();
    $order = makeAlphaOrder($user, '10000.000000');

    (new AlphaRewardService(false))->run(now()->toDateString());
    (new AlphaRewardService(false))->run(now()->toDateString());

    expect(AlphaReward::query()->where('order_id', $order->id)->count())->toBe(1);
    expect(AlphaOrder::find($order->id)->rel_reward)->toBe('56.666667');
    expect(UserData::getInfo($user->id)->stake_reward)->toBe('56.666667'); // 累计收益
});

test('累计收益达到封顶时订单自动完成', function () {
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
    $power_am = bcmul('1000.000000', $product->per_power, 6);
    $order = AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => '1000.000000',
        'power_am' => $power_am,
        'token_price' => '1.00000000',
        'reward_rate' => '17.00',
        'quota_reward' => '2500.000000',
        'rem_reward' => '1.000000',   // 只差 1
        'rel_reward' => '2499.000000',
        'status' => AlphaOrder::STATUS_PAID,
    ]);
    // 申购回调已把质押/额度槽入账：本金 1000 → curr_stake/current_slot 各 +1000
    UserData::query()->where('user_id', $user->id)->update([
        'curr_stake' => '1000.000000',
        'current_slot' => '1000.000000',
    ]);

    (new AlphaRewardService(false))->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->status)->toBe(AlphaOrder::STATUS_DONE);
    expect($fresh->done_at)->not()->toBeNull();
    expect($fresh->rel_reward)->toBe('2500.000000'); // 以 rem_reward 封顶
    expect($fresh->rem_reward)->toBe('0.000000');
    expect($fresh->power_am)->toBe($power_am);
    // 订单完成：减去当前质押量 + 释放额度槽
    $data = UserData::getInfo($user->id);
    expect($data->curr_stake)->toBe('0.000000');
    expect($data->current_slot)->toBe('0.000000');
    expect($data->stake_reward)->toBe('1.000000'); // 累计收益
});
