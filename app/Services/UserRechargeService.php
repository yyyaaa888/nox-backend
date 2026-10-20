<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\UserRecharge;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;

class UserRechargeService
{
    public static function createOrder(User $user, array $param, array $pay)
    {
        $amount = $param['amount']; // 充值金额
        $symbol = $param['symbol']; // 充值币种
        $asset  = $param['asset']; // 支付币种

        $sn = randOrderNo(); // 单号

        // 创建订单
        $order          = new UserRecharge();
        $order->user_id = $user->id;
        $order->sn      = $sn;
        $order->amount  = $amount;
        $order->symbol  = $symbol;
        $order->status  = UserRecharge::STATUS_NOPAY;
        $order->save();

        // 创建支付单
        $payOrder               = new PayOrder();
        $payOrder->user_id      = $user->id;
        $payOrder->order_id     = $order->id;
        $payOrder->sn           = $sn;
        $payOrder->type         = PayOrder::TYPE_RECHARGE;
        $payOrder->amount       = $amount;
        $payOrder->decimals     = $pay['decimals'];
        $payOrder->symbol       = $asset;
        $payOrder->pay_contract = $pay['pay_contract'];
        $payOrder->pay_addr     = $pay['pay_addr'];
        $payOrder->token_addr   = $pay['token_addr'];
        $payOrder->dest_addr    = $pay['dest_addr'];
        $payOrder->last_time    = $pay['last_time'];
        $payOrder->status       = PayOrder::STATUS_WAIT;
        $payOrder->save();

        return [$order, $payOrder];
    }

    /**
     * 支付回调
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function payCallback(UserRecharge $order, PayOrder $payOrder, ?string $hash, string $pay_total)
    {
        $now = Carbon::now();
        // 更新支付单状态：已支付
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'    => PayOrder::STATUS_PAID,
            'hash'      => $hash,
            'pay_total' => $pay_total,
            'pay_at'    => $now,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        // 更新订单状态：已支付
        $re2 = UserRecharge::query()->where('id', $order->id)->where('status', UserRecharge::STATUS_NOPAY)->update([
            'status' => UserRecharge::STATUS_PAID,
            'pay_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }

        // 更新用户充值金额
        WalletLog::addLog($order->user_id, $order->amount, $order->symbol, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_RECHARGE_IN);
    }

    /**
     * 支付取消处理
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function payCancel(UserRecharge $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        $now = Carbon::now();
        // 更新支付单状态：取消
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'      => PayOrder::STATUS_CANCEL,
            'fail_at'     => $now,
            'fail_reason' => $fail_reason,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        // 更新订单状态：取消
        $re2 = UserRecharge::query()->where('id', $order->id)->where('status', UserRecharge::STATUS_NOPAY)->update([
            'status'  => UserRecharge::STATUS_CANCEL,
            'fail_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }

    /**
     * 支付超时取消处理
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function payTimeout(UserRecharge $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        $now = Carbon::now();
        // 更新支付单状态：超时取消
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'      => PayOrder::STATUS_TIMEOUT,
            'fail_at'     => $now,
            'fail_reason' => $fail_reason,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        // 更新订单状态：取消
        $re2 = UserRecharge::query()->where('id', $order->id)->where('status', UserRecharge::STATUS_NOPAY)->update([
            'status'  => UserRecharge::STATUS_TIMEOUT,
            'fail_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }

    /**
     * 支付失败处理
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function payFail(UserRecharge $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        $now = Carbon::now();
        // 更新支付单状态：失败
        $re = PayOrder::query()->where('id', $payOrder->id)->where('status', PayOrder::STATUS_WAIT)->update([
            'status'      => PayOrder::STATUS_FAIL,
            'fail_at'     => $now,
            'fail_reason' => $fail_reason,
        ]);
        if (! $re) {
            throw new LogicException(__('支付状态异常'));
        }
        // 更新订单状态：失败
        $re2 = UserRecharge::query()->where('id', $order->id)->where('status', UserRecharge::STATUS_NOPAY)->update([
            'status'  => UserRecharge::STATUS_FAIL,
            'fail_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }
}
