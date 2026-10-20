<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\NodeOrder;
use App\Models\PayOrder;
use App\Models\RewardRate;
use App\Models\TeamReward;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaOrderService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('奖励表、模型与常量已注册', function () {
    expect(WalletLog::TYPE_INVITE_REWARD)->toBe(150);
    expect(WalletLog::TYPE_TEAM_REWARD)->toBe(151);
    expect(WalletLog::TYPE_SAME_LEVEL_REWARD)->toBe(152);

    // 率表种子
    expect(RewardRate::count())->toBe(76);
    $v3NodeInviteL1 = RewardRate::query()
        ->where('type', 'invite')->where('rank', 3)->where('has_node', 1)->where('layer', 1)
        ->first()->rate;
    expect($v3NodeInviteL1)->toBe('10.00');
    $v5TeamBaseL5 = RewardRate::query()
        ->where('type', 'team')->where('rank', 5)->where('has_node', 0)->where('layer', 5)
        ->first()->rate;
    expect($v5TeamBaseL5)->toBe('2.00');
    // V1 只有 3 层，layer 4 无行
    expect(RewardRate::query()->where('type', 'invite')->where('rank', 1)->where('layer', 4)->exists())->toBeFalse();

    $t = TeamReward::create([
        'user_id' => 1,
        'child_id' => 2,
        'order_id' => 3,
        'level' => 3,
        'prev_level' => 0,
        'child_level' => 1,
        'layer' => 1,
        'has_node' => 1,
        'is_same_level' => 0,
        'order_am' => '100.000000',
        'reward_am' => '10.000000',
        'reward_rate' => '10.00',
        'sett_date' => now()->toDateString(),
    ]);
    expect(TeamReward::find($t->id)->layer)->toBe(1);
});

function makeRewardUser(string $address, int $levelId): User
{
    $u = User::factory()->create(['status' => 1, 'invited_uid' => 0, 'level_id' => $levelId, 'address' => $address]);
    UserData::addInfo($u->id);

    return $u;
}

test('distributeInvite 按层级与费率发放推广奖励', function () {
    // V5 持节点上级(直推) + V1 下线购买
    $up = makeRewardUser('0x'.str_repeat('a', 40), 5);
    $down = makeRewardUser('0x'.str_repeat('b', 40), 1);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    UserData::query()->where('user_id', $up->id)->update(['node_id' => 1, 'node_num' => 1, 'node_at' => now()]);
    // 节点持有：既有 user_datas.node_id（has_node 判定）也有在持 NodeOrder（额度槽来源），与实际购买流程一致
    NodeOrder::create([
        'user_id' => $up->id,
        'product_id' => 1,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => '1000.000000',
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    UserData::query()->where('user_id', $up->id)->update([
        'current_slot' => DB::raw('current_slot + 1000'),
    ]);
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
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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

    RewardService::distributeInvite($order);

    // V5 持节点直推 = 1000 × 12% = 120
    expect(UserData::getInfo($up->id)->current_nox)->toBe('120.000000');
    expect(UserData::getInfo($up->id)->invite_reward)->toBe('120.000000');
    expect(\App\Models\InviteReward::count())->toBe(1);
    $ir = \App\Models\InviteReward::first();
    expect($ir->user_id)->toBe($up->id);
    expect($ir->reward_am)->toBe('120.000000');
    expect($ir->reward_rate)->toBe('12.00');
});

test('distributeInvite 按订单幂等', function () {
    $up = makeRewardUser('0x'.str_repeat('a', 40), 5);
    $down = makeRewardUser('0x'.str_repeat('b', 40), 1);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    UserData::query()->where('user_id', $up->id)->update(['node_id' => 1, 'node_num' => 1, 'node_at' => now()]);
    // 节点持有：既有 user_datas.node_id（has_node 判定）也有在持 NodeOrder（额度槽来源），与实际购买流程一致
    NodeOrder::create([
        'user_id' => $up->id,
        'product_id' => 1,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => '1000.000000',
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    UserData::query()->where('user_id', $up->id)->update([
        'current_slot' => DB::raw('current_slot + 1000'),
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
        'name' => '基础算力方案',
        'tier' => AlphaProduct::TIER_BASIC,
        'min_amount' => '100.000000',
        'max_amount' => '30000.000000',
        'per_power' => '1.0000000',
        'base_rate' => '17.00',
        'node_rate' => '20.00',
        'status' => AlphaProduct::STATUS_ON,
        'sort' => 1,
    ]);
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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

    RewardService::distributeInvite($order);
    RewardService::distributeInvite($order);

    expect(\App\Models\InviteReward::count())->toBe(1);
    expect(UserData::getInfo($up->id)->current_nox)->toBe('120.000000');
});

test('distributeTeam 同级上级只发平级奖10%、不发全额团队奖励', function () {
    // V3 持节点上级(直推)、下线 V3（同等级）
    $up = makeRewardUser('0x'.str_repeat('c', 40), 3);
    $down = makeRewardUser('0x'.str_repeat('d', 40), 3);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    UserData::query()->where('user_id', $up->id)->update(['node_id' => 1, 'node_num' => 1, 'node_at' => now()]);
    // 节点持有：既有 user_datas.node_id（has_node 判定）也有在持 NodeOrder（额度槽来源），与实际购买流程一致
    NodeOrder::create([
        'user_id' => $up->id,
        'product_id' => 1,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => '1000.000000',
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    UserData::query()->where('user_id', $up->id)->update([
        'current_slot' => DB::raw('current_slot + 1000'),
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
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
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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
    $static = '56.66666666'; // 静态收益额

    RewardService::distributeTeam($order, now()->toDateString(), $static);

    // V3 持节点直推团队率 layer1 = 7% → 56.66666666 × 7% = 3.9666666662 → 3.966667
    $teamAm = bcround2(bcdiv(bcmul($static, '7.00', 8), '100', 8), 6);
    // 同级 → 只发平级奖 = 团队奖 × 10% = 0.396667（不发全额团队奖励）
    $sameAm = bcround2(bcdiv(bcmul($teamAm, '10', 8), '100', 8), 6);
    expect(UserData::getInfo($up->id)->current_nox)->toBe($sameAm);
    expect(UserData::getInfo($up->id)->team_reward)->toBe($sameAm);
    $tr = TeamReward::first();
    expect($tr->user_id)->toBe($up->id);
    expect($tr->layer)->toBe(1);
    expect($tr->has_node)->toBe(1);
    expect($tr->is_same_level)->toBe(1);
    expect($tr->reward_am)->toBe($sameAm);
});

test('distributeTeam 平级奖励跳过非同等级祖先', function () {
    // V5 上级(直推)、下线 V1 → 不同等级 → 无平级奖，只有团队奖
    $up = makeRewardUser('0x'.str_repeat('e', 40), 5);
    $down = makeRewardUser('0x'.str_repeat('f', 40), 1);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
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
    // 上级持有一个在持资产包（非节点）→ 额度槽为正，团队收益入可用；同时保持 has_node=0
    AlphaOrder::create([
        'user_id' => $up->id,
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
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $up->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);

    RewardService::distributeTeam($order, now()->toDateString(), '56.66666666');

    $tr = TeamReward::first();
    expect($tr->is_same_level)->toBe(0);
    // V5 基础团队率 layer1 = 7% → 3.966667，无平级
    expect(UserData::getInfo($up->id)->current_nox)->toBe('3.966667');
});

test('distributeTeam 上级低于下级不发团队奖励', function () {
    // V1 上级(直推)、下线 V3 → 上级低于下级，不发放团队奖励也不产生记录
    $up = makeRewardUser('0x'.str_repeat('1', 40), 1);
    $down = makeRewardUser('0x'.str_repeat('2', 40), 3);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
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
    // 上级持有一个在持资产包（非节点）→ 额度槽为正，若有奖励会入可用
    AlphaOrder::create([
        'user_id' => $up->id,
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
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    // 上级已有额度槽（额度槽来源：在持资产包）
    UserData::query()->where('user_id', $up->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);

    RewardService::distributeTeam($order, now()->toDateString(), '56.66666666');

    // 上级低于下级：不入账、不产生记录
    expect(TeamReward::count())->toBe(0);
    expect(UserData::getInfo($up->id)->current_nox)->toBe('0.000000');
    expect(UserData::getInfo($up->id)->alpha_frozen)->toBe('0.000000');
});

test('用户资金流水类型映射包含奖励类型', function () {
    $map = WalletLog::TYPE_MAP;
    expect($map)->toHaveKey(WalletLog::TYPE_INVITE_REWARD, '推广收益')
        ->toHaveKey(WalletLog::TYPE_TEAM_REWARD, '团队收益')
        ->toHaveKey(WalletLog::TYPE_SAME_LEVEL_REWARD, '平级奖');

    $userMap = WalletLog::getTypeMapUser();
    expect($userMap)->toHaveKey(WalletLog::TYPE_INVITE_REWARD, '推广收益')
        ->toHaveKey(WalletLog::TYPE_TEAM_REWARD, '团队收益')
        ->toHaveKey(WalletLog::TYPE_SAME_LEVEL_REWARD, '平级奖');

    $usdtFilter = WalletLog::getTypeMapUserFilter(WalletLog::SYMBOL_USDT);
    expect($usdtFilter)->toHaveKey(WalletLog::TYPE_INVITE_REWARD, '推广收益')
        ->toHaveKey(WalletLog::TYPE_TEAM_REWARD, '团队收益')
        ->toHaveKey(WalletLog::TYPE_SAME_LEVEL_REWARD, '平级奖');
});

test('distributeTeam 按（订单, 结算日）幂等', function () {
    // V3 持节点上级(直推)、下线 V3（同等级），同 (order, sett_date) 分发两次
    $up = makeRewardUser('0x'.str_repeat('a', 40), 3);
    $down = makeRewardUser('0x'.str_repeat('b', 40), 3);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    UserData::query()->where('user_id', $up->id)->update(['node_id' => 1, 'node_num' => 1, 'node_at' => now()]);
    // 节点持有：既有 user_datas.node_id（has_node 判定）也有在持 NodeOrder（额度槽来源），与实际购买流程一致
    NodeOrder::create([
        'user_id' => $up->id,
        'product_id' => 1,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => '1000.000000',
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
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
    $nodeAm = '1000.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $up->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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
    $static = '56.66666666';
    $settle = now()->toDateString();

    RewardService::distributeTeam($order, $settle, $static);
    RewardService::distributeTeam($order, $settle, $static);

    // 第二次调用应为空操作：不发新行、不重复入账
    expect(TeamReward::count())->toBe(1);
    $teamAm = bcround2(bcdiv(bcmul($static, '7.00', 8), '100', 8), 6);
    // 同级 → 只发平级奖 10%
    $sameAm = bcround2(bcdiv(bcmul($teamAm, '10', 8), '100', 8), 6);
    expect(UserData::getInfo($up->id)->current_nox)->toBe($sameAm);
    expect(UserData::getInfo($up->id)->team_reward)->toBe($sameAm);
});

test('上级无资产包/节点额度槽时推广奖励全额冻结', function () {
    // V5 基础直推(无节点、无在持资产包) → 额度槽 S=0，奖励全额冻结
    $up = makeRewardUser('0x'.str_repeat('a', 40), 5);
    $down = makeRewardUser('0x'.str_repeat('b', 40), 1);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    $product = AlphaProduct::first() ?: AlphaProduct::create([
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
    $nodeAm = '0.000000';
    $alphaAm = '1000.000000';
    $power_am = bcmul($alphaAm, $product->per_power, 6);
    $totalAm = bcadd($alphaAm, $nodeAm, 6);
    UserData::query()->where('user_id', $down->id)->update([
        'total_stake' => DB::raw('total_stake + '.$alphaAm),
        'curr_stake' => DB::raw('curr_stake + '.$alphaAm),
        'total_power' => DB::raw('total_power + '.$power_am),
        'curr_power' => DB::raw('curr_power + '.$power_am),
        'current_slot' => DB::raw('current_slot + '.$totalAm),
    ]);
    $order = AlphaOrder::create([
        'user_id' => $down->id,
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

    RewardService::distributeInvite($order);

    // V5 基础直推层1率 = 8% → 1000 × 8% = 80，全额进冻结，可用为 0
    expect(UserData::getInfo($up->id)->current_nox)->toBe('0.000000');
    expect(UserData::getInfo($up->id)->alpha_frozen)->toBe('80.000000');
    expect(UserData::getInfo($up->id)->alpha_quota_used)->toBe('0.000000');
});

test('payCallback 向推荐链全链路分发推广奖励', function () {
    // V5 持节点上级(直推) + V1 下线，下单走 createOrder + payCallback（status 从 WAIT 翻 PAID）
    $up = makeRewardUser('0x'.str_repeat('a', 40), 5);
    $down = makeRewardUser('0x'.str_repeat('b', 40), 1);
    User::query()->where('id', $down->id)->update(['invited_uid' => $up->id]);
    DB::table('user_relation_closures')->insert([
        'ancestor_id' => $up->id,
        'descendant_id' => $down->id,
        'depth' => 1,
    ]);
    UserData::query()->where('user_id', $up->id)->update(['node_id' => 1, 'node_num' => 1, 'node_at' => now()]);
    // 节点持有：既有 user_datas.node_id（has_node 判定）也有在持 NodeOrder（额度槽来源），与实际购买流程一致
    NodeOrder::create([
        'user_id' => $up->id,
        'product_id' => 1,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => '1000.000000',
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    UserData::query()->where('user_id', $up->id)->update([
        'current_slot' => DB::raw('current_slot + 1000'),
    ]);
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
        'pay_addr' => $down->address,
        'token_addr' => '0x'.str_repeat('b', 40),
        'token_price' => '1.00000000',
        'dest_addr' => '0x'.str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = AlphaOrderService::createOrder($down, $product, '1000.000000', $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    expect($order->status)->toBe(AlphaOrder::STATUS_WAIT);

    AlphaOrderService::payCallback($order, $payOrder, '0x'.str_repeat('f', 64), '1000.000000');

    // V5 持节点直推 = 1000 × 12% = 120
    expect(UserData::getInfo($up->id)->invite_reward)->toBe('120.000000');
    expect(UserData::getInfo($up->id)->current_nox)->toBe('120.000000');
    expect(\App\Models\InviteReward::count())->toBe(1);
});
