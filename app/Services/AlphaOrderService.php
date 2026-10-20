<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\AlphaOrder;
use App\Models\AlphaProduct;
use App\Models\PayOrder;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserPerfLog;
use App\Models\UserPowerLog;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AlphaOrderService
{
    /**
     * 创建资产包申购订单 + 支付单（链上直付，不扣内部余额）
     */
    public static function createOrder(User $user, AlphaProduct $product, string $amount, array $pay): AlphaOrder
    {
        $sn  = randOrderNo();
        $now = Carbon::now();

        $token_price = $pay['token_price'];
        $power_am    = bcmul($amount, $product->per_power, 6);

        $quota_reward_usdt = bcmul($amount, getGlobalConfig('alpha_quota_multiple', '2.5'), 6);
        $quota_reward      = bcdiv($quota_reward_usdt, $token_price, 6);

        $order               = new AlphaOrder();
        $order->user_id      = $user->id;
        $order->product_id   = $product->id;
        $order->sn           = $sn;
        $order->amount       = $amount;
        $order->power_am     = $power_am;
        $order->token_price  = $token_price;
        $order->reward_rate  = $product->base_rate;
        $order->quota_reward = $quota_reward;
        $order->rem_reward   = $quota_reward;
        $order->trial_end    = $now->copy()->addDays(30);
        $order->status       = AlphaOrder::STATUS_WAIT;
        $order->save();

        $payOrder               = new PayOrder();
        $payOrder->user_id      = $user->id;
        $payOrder->order_id     = $order->id;
        $payOrder->sn           = $sn;
        $payOrder->type         = PayOrder::TYPE_ALPHA_BUY;
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
     * 支付确认：激活订单（待支付→进行中）并触发解冻
     */
    public static function payCallback(AlphaOrder $order, PayOrder $payOrder, ?string $hash, string $pay_total)
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
        $re2 = AlphaOrder::query()->where('id', $order->id)->where('status', AlphaOrder::STATUS_WAIT)->update([
            'status' => AlphaOrder::STATUS_PAID,
            'pay_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }

        // 计算额度槽(NOXO) = 购买金额(USDT) / NOXO价格
        $slot = bcdiv($order->amount, $order->token_price, 6);

        // 更新用户质押金额 + 额度槽
        UserData::query()->where('user_id', $order->user_id)->update([
            'total_stake'  => DB::raw('total_stake + '.$order->amount),
            'curr_stake'   => DB::raw('curr_stake + '.$order->amount),
            'current_slot' => DB::raw('current_slot + '.$slot), // 额度槽
        ]);
        // 更新用户业绩
        UserPerfLog::addLog($order->user_id, $order->amount, UserPerfLog::TYPE_ALPHA_BUY, UserPerfLog::INC, '资产包申购');
        // 更新用户算力值
        UserPowerLog::addLog($order->user_id, $order->power_am, UserPowerLog::TYPE_ALPHA_BUY, UserPowerLog::INC, '资产包申购');

        // 上面的 update() 是查询构造器，不会刷新内存模型 → 手动 refresh，让 status 变为 PAID
        $order->refresh();
        // 本金入槽 → 解冻
        AlphaQuotaService::sweep($order->user_id);
        // 推广收益：下线购买资产包，向推荐链分发
        \App\Services\RewardService::distributeInvite($order);
    }

    public static function payCancel(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_CANCEL, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payTimeout(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_TIMEOUT, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    public static function payFail(AlphaOrder $order, PayOrder $payOrder, ?string $fail_reason = null)
    {
        self::markTerminal($order, $payOrder, PayOrder::STATUS_FAIL, AlphaOrder::STATUS_CANCEL, $fail_reason);
    }

    private static function markTerminal(AlphaOrder $order, PayOrder $payOrder, int $payStatus, int $orderStatus, ?string $fail_reason)
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
        $re2 = AlphaOrder::query()->where('id', $order->id)->where('status', AlphaOrder::STATUS_WAIT)->update([
            'status'  => $orderStatus,
            'fail_at' => $now,
        ]);
        if (! $re2) {
            throw new LogicException(__('订单状态异常'));
        }
    }

    /**
     * 首月试用退出：退 80% 本金入可用余额，订单终止
     */
    public static function exit(AlphaOrder $order)
    {
        DB::transaction(function () use ($order) {
            $now = Carbon::now();
            $res = AlphaOrder::query()
                ->where('id', $order->id)
                ->where('status', AlphaOrder::STATUS_PAID)
                ->where('trial_end', '>=', $now)
                ->update([
                    'status'  => AlphaOrder::STATUS_EXITED,
                    'exit_at' => $now,
                ]);
            if (! $res) {
                throw new LogicException(__('当前订单不可退出'));
            }
            // 扣除该订单对应的质押金额 + 额度槽（首月退出，质押/额度槽随之减少）
            UserData::query()->where('user_id', $order->user_id)->where('curr_stake', '>=', $order->amount)->update([
                'curr_stake'   => DB::raw('curr_stake - '.$order->amount),
                'current_slot' => DB::raw('current_slot - '.$order->amount), // 额度槽
            ]);
            // 更新用户业绩
            UserPerfLog::addLog($order->user_id, $order->amount, UserPerfLog::TYPE_ALPHA_EXIT, UserPerfLog::DEC, '资产包退出');
            // 更新用户算力值
            UserPowerLog::addLog($order->user_id, $order->power_am, UserPowerLog::TYPE_ALPHA_EXIT, UserPowerLog::DEC, '资产包退出');
            $refund = bcmul($order->amount, '0.8', 6);
            WalletLog::addLog($order->user_id, $refund, WalletLog::SYMBOL_USDT, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_ALPHA_EXIT);
        });
    }
}
