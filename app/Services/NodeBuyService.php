<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\NodeOrder;
use App\Models\NodeProduct;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NodeBuyService
{
    /**
     * 创建节点申购订单 + 支付单（链上直付，不扣内部余额）
     */
    public static function createOrder(User $user, NodeProduct $product, int $num, array $pay): NodeOrder
    {
        $amount = bcmul($product->price, $num, 6);

        $sn = randOrderNo();

        $order             = new NodeOrder();
        $order->user_id    = $user->id;
        $order->product_id = $product->id;
        $order->sn         = $sn;
        $order->num        = $num;
        $order->price      = $product->price;
        $order->amount     = $amount;
        $order->status     = NodeOrder::STATUS_WAIT;
        $order->save();

        $payOrder               = new PayOrder();
        $payOrder->user_id      = $user->id;
        $payOrder->order_id     = $order->id;
        $payOrder->sn           = $sn;
        $payOrder->type         = PayOrder::TYPE_NODE_BUY;
        $payOrder->amount       = $amount;
        $payOrder->decimals     = $pay['decimals'];
        $payOrder->symbol       = WalletLog::SYMBOL_USDT;
        $payOrder->pay_contract = $pay['pay_contract'];
        $payOrder->pay_addr     = $pay['pay_addr'];
        $payOrder->token_addr   = $pay['token_addr'];
        $payOrder->dest_addr    = $pay['dest_addr'];
        $payOrder->last_time    = $pay['last_time'];
        $payOrder->status       = PayOrder::STATUS_WAIT;
        $payOrder->save();

        return $order;
    }

    /**
     * 支付确认：节点永久持有（无退出、无产出，不触发 sweep）
     */
    public static function payCallback(NodeOrder $order, PayOrder $payOrder, ?string $hash, string $pay_total)
    {
        $now = Carbon::now();
        $re  = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'    => PayOrder::STATUS_PAID,
            'hash'      => $hash,
            'pay_total' => $pay_total,
            'pay_at'    => $now,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        $re2 = NodeOrder::query()->where('id', $order->id)->where('status', NodeOrder::STATUS_WAIT)->update([
            'status' => NodeOrder::STATUS_PAID,
            'pay_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }

        // 更新用户持有节点
        UserData::query()->where('user_id', $order->user_id)->update([
            'node_id'      => $order->product_id,
            'node_num'     => DB::raw('node_num + '.$order->num),
            'node_at'      => $now,
            'current_slot' => DB::raw('current_slot + '.$order->amount), // 额度槽
        ]);
    }

    public static function payCancel(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_CANCEL, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payTimeout(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_TIMEOUT, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payFail(NodeOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_FAIL, NodeOrder::STATUS_CANCEL, $fail_reason);
    }

    private static function markTerminal(NodeOrder $order, PayOrder $payOrder, int $payStatus, int $orderStatus, ?string $fail_reason)
    {
        $now = Carbon::now();
        $re  = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'      => $payStatus,
            'fail_at'     => $now,
            'fail_reason' => $fail_reason,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        $re2 = NodeOrder::query()->where('id', $order->id)->where('status', NodeOrder::STATUS_WAIT)->update([
            'status'  => $orderStatus,
            'fail_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }
}
