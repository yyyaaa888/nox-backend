<?php

use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\UserData;
use App\Models\WalletLog;
use App\Services\AlphaQuotaService;
use App\Services\NodeBuyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// 测试节点订单模型
test('节点常量与模型已注册', function () {
    expect(PayOrder::TYPE_NODE_BUY)->toBe(3);
    expect(WalletLog::TYPE_NODE_FEE_IN)->toBe(140);
    expect(WalletLog::TYPE_NODE_FEE_PROJECT)->toBe(141);

    $product = NodeProduct::create([
        'name' => '算力加速节点',
        'price' => '1000.000000',
        'status' => NodeProduct::STATUS_ON,
        'sort' => 1,
    ]);
    expect(NodeProduct::find($product->id)->price)->toBe('1000.000000');

    $order = NodeOrder::create([
        'user_id' => 1,
        'product_id' => $product->id,
        'sn' => randOrderNo(),
        'num' => 1,
        'price' => $product->price,
        'amount' => '1000.000000',
        'status' => NodeOrder::STATUS_PAID,
    ]);
    expect(NodeOrder::getBySn($order->sn)->id)->toBe($order->id);
    expect(NodeOrder::STATUS_MAP[NodeOrder::STATUS_PAID])->toBe('持有中');

    $alloc = NodeFeeAllocation::create([
        'sett_date' => now()->toDateString(),
        'total_fee' => '100.000000',
        'node_fee_am' => '90.000000',
        'node_count' => 2,
        'per_share' => '45.000000',
        'project_am' => '10.000000',
        'remain' => '0.000000',
        'status' => NodeFeeAllocation::STATUS_DONE,
    ]);
    expect(NodeFeeAllocation::find($alloc->id)->per_share)->toBe('45.000000');

    NodeFeeLog::create([
        'sett_date' => now()->toDateString(),
        'alloc_id' => $alloc->id,
        'user_id' => 1,
        'am' => '45.000000',
        'node_num' => 1,
        'total_num' => 2,
        'total_am' => '90.000000',
        'per_share' => '45.000000',
    ]);
    expect(NodeFeeLog::count())->toBe(1);
});

// 测试创建节点订单
test('createOrder 构建节点订单与支付单', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct('1000.000000');
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $num = 1;
    $amount = bcmul($num, $product->price, 6);

    $order = NodeBuyService::createOrder($user, $product, $num, $pay);

    expect($order->num)->toBe($num);
    expect($order->price)->toBe($product->price);
    expect($order->amount)->toBe($amount);
    expect($order->status)->toBe(NodeOrder::STATUS_WAIT);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();
    expect($payOrder->type)->toBe(PayOrder::TYPE_NODE_BUY);
    expect($payOrder->order_id)->toBe($order->id);
    expect($payOrder->amount)->toBe($amount);
});

// 测试支付回调
test('payCallback 永久激活节点订单', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, 1, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    NodeBuyService::payCallback($order, $payOrder, '0x' . str_repeat('f', 64), '1000.000000');

    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_PAID);
    expect(NodeOrder::find($order->id)->pay_at)->not->toBeNull();
    expect(UserData::getInfo($order->user_id)->node_id)->toBe($order->product_id);
    expect(UserData::getInfo($order->user_id)->node_num)->toBe(1);
    expect(UserData::getInfo($order->user_id)->node_at)->not->toBeNull();
});

// 测试支付取消回调
test('payCancel 将订单与支付单标记为已取消', function () {
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, 1, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    NodeBuyService::payCancel($order, $payOrder, '用户取消支付');

    expect(PayOrder::find($payOrder->id)->status)->toBe(PayOrder::STATUS_CANCEL);
    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_CANCEL);
});

// 测试订单支付
test('order-pay 完成节点支付单', function () {
    config(['app.check.payOrder' => false]);
    $user = makeNodeUser();
    $product = makeNodeProduct();
    $pay = [
        'pay_contract' => '0x' . str_repeat('a', 40),
        'pay_addr' => $user->address,
        'token_addr' => '0x' . str_repeat('b', 40),
        'dest_addr' => '0x' . str_repeat('d', 40),
        'last_time' => now()->addMinutes(5),
        'decimals' => 18,
    ];
    $order = NodeBuyService::createOrder($user, $product, 1, $pay);
    $payOrder = PayOrder::query()->where('sn', $order->sn)->first();

    $cmd = new \App\Console\Commands\OrderPayCmd();
    $cmd->setOutput(new \Illuminate\Console\OutputStyle(
        new \Symfony\Component\Console\Input\ArrayInput([]),
        new \Symfony\Component\Console\Output\BufferedOutput()
    ));
    $result = $cmd->processOrder($payOrder);

    expect($result)->toBe('success');
    expect(NodeOrder::find($order->id)->status)->toBe(NodeOrder::STATUS_PAID);
});

// 测试当前槽位
test('currentSlot 包含节点本金', function () {
    $user = makeNodeUser();
    makeNodeOrder($user, '1000.000000'); // node 1000
    makeAlphaOrderForNode($user, '1000.000000'); // alpha 1000

    $slot = AlphaQuotaService::currentSlot($user->id);

    expect($slot)->toBe('5000.000000'); // 2.5 × (1000+1000)
});

// 测试结算 有节点
test('持节点用户结算采用 node_rate', function () {
    seedChainAndAllAsset();
    $user = makeNodeUser();
    makeNodeOrder($user, '1000.000000'); // 持有节点
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
    $order = AlphaOrder::create([
        'user_id' => $user->id,
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

    (new \App\Services\AlphaRewardService(false))->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('6.666667'); // 1000×20/3000=6.66666666→6.666667
});

// 测试结算 无节点
test('未持节点用户结算采用产品基础费率', function () {
    seedChainAndAllAsset();
    $user = makeNodeUser();
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
    $order = AlphaOrder::create([
        'user_id' => $user->id,
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

    (new \App\Services\AlphaRewardService(false))->run(now()->toDateString());

    $fresh = AlphaOrder::find($order->id);
    expect($fresh->rel_reward)->toBe('5.666667'); // 1000×17/3000=5.66666666→5.666667
});
