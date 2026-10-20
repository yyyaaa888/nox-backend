<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function makeAlphaUser(): User
{
    $user = User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    UserData::addInfo($user->id);
    return $user;
}

function makeAlphaOrder(User $user, string $amount, string $rate = '17.00'): AlphaOrder
{
    $product = AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => $rate,
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $power_am = bcmul($amount, $product->per_power, 6);
    $order = AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => $amount,
        'power_am' => $power_am,
        'token_price' => '1.00000000',
        'reward_rate' => $rate,
        'quota_reward' => bcmul($amount, '2.5', 6),
        'rem_reward' => bcmul($amount, '2.5', 6),
        'trial_end' => now()->addDays(30),
        'status' => AlphaOrder::STATUS_PAID,
    ]);
    // 计算额度槽(NOXO) = 购买金额(USDT) / NOXO价格
    $slot = bcdiv($order->amount, $order->token_price, 6);
    UserData::query()->where('user_id', $user->id)->update([
        'total_stake' => DB::raw('total_stake + ' . $amount),
        'curr_stake' => DB::raw('curr_stake + ' . $amount),
        'total_power' => DB::raw('total_power + ' . $power_am),
        'curr_power' => DB::raw('curr_power + ' . $power_am),
        'current_slot' => DB::raw('current_slot + ' . $slot),
    ]);
    return $order;
}

function makeNodeUser(): \App\Models\User
{
    $user = \App\Models\User::factory()->create(['status' => 1, 'invited_uid' => 0]);
    \App\Models\UserData::addInfo($user->id);
    return $user;
}

function makeNodeProduct(string $price = '1000.000000'): \App\Models\NodeProduct
{
    return \App\Models\NodeProduct::create([
        'name' => '算力加速节点',
        'price' => $price,
        'status' => \App\Models\NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);
}

function makeNodeOrder(\App\Models\User $user, string $amount = '1000.000000', int $status = \App\Models\NodeOrder::STATUS_PAID, int $num = 1): \App\Models\NodeOrder
{
    $product = makeNodeProduct();
    $order = \App\Models\NodeOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'num' => $num,
        'price' => $amount,
        'amount' => bcmul($amount, (string) $num, 6),
        'status' => $status,
    ]);
    // 与 NodeBuyService::payCallback 保持一致：持节点在 user_datas 上落 node_id/node_num/node_at
    if ($status === \App\Models\NodeOrder::STATUS_PAID) {
        \App\Models\UserData::query()->where('user_id', $user->id)->update([
            'node_id' => $product->id,
            'node_num' => \Illuminate\Support\Facades\DB::raw('node_num + ' . $order->num),
            'node_at' => now(),
        ]);
        UserData::query()->where('user_id', $user->id)->update([
            'current_slot' => DB::raw('current_slot + ' . $order->amount),
        ]);
    }
    return $order;
}

function makeAlphaOrderForNode(\App\Models\User $user, string $amount, string $rate = '17.00'): \App\Models\AlphaOrder
{
    $product = \App\Models\AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => \App\Models\AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.000000',
        'base_rate' => $rate,
        'node_rate' => '20.00',
        'status' => \App\Models\AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $power_am = bcmul($amount, $product->per_power, 6);
    UserData::query()->where('user_id', $user->id)->update([
        'total_stake' => DB::raw('total_stake + ' . $amount),
        'curr_stake' => DB::raw('curr_stake + ' . $amount),
        'total_power' => DB::raw('total_power + ' . $power_am),
        'curr_power' => DB::raw('curr_power + ' . $power_am),
        'current_slot' => DB::raw('current_slot + ' . $amount),
    ]);
    return \App\Models\AlphaOrder::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'amount' => $amount,
        'power_am' => $power_am,
        'token_price' => '1.00000000',
        'reward_rate' => $rate,
        'quota_reward' => bcmul($amount, '2.5', 6),
        'rem_reward' => bcmul($amount, '2.5', 6),
        'status' => \App\Models\AlphaOrder::STATUS_PAID,
    ]);
}

function seedChainAndAllAsset(): void
{
    config(['site.asset.chain_id' => '97']);
    \Illuminate\Support\Facades\DB::table('chains')->insertOrIgnore([
        'id' => 1,
        'name' => 'bsc',
        'alias' => 'bsc',
        'chain_id' => '97',
        'symbol' => 'BNB',
        'decimals' => 18,
        'rpc_url' => 'https://example.com',
        'scan_url' => '',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('assets')->insertOrIgnore([
        'id' => 1,
        'chain_id' => 97,
        'name' => 'USDT',
        'title' => 'USDT测试币',
        'code' => 'USDT',
        'decimals' => 18,
        'type' => 2,
        'status' => 1,
        'is_exchange' => 1,
        'exchange_rate' => '1.00000000',
        'contract_abi' => '[]',
        'contract_address' => '0x'.str_repeat('b', 40),
        'withdraw_address' => '',
        'withdraw_key' => '',
        'wallet_address' => '0x'.str_repeat('d', 40),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('assets')->insertOrIgnore([
        'id' => 2,
        'chain_id' => 97,
        'name' => 'NOXO',
        'title' => 'NOXO测试币',
        'code' => 'NOX',
        'decimals' => 18,
        'type' => 2,
        'status' => 1,
        'is_exchange' => 1,
        'exchange_rate' => '1.00000000',
        'contract_abi' => '[]',
        'contract_address' => '0x' . str_repeat('b', 40),
        'withdraw_address' => '',
        'withdraw_key' => '',
        'wallet_address' => '',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
