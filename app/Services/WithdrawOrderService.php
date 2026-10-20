<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\FeeLog;
use App\Models\User;
use App\Models\WalletLog;
use App\Models\Withdraw;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawOrderService
{
    public static function createOrder(User $user, array $param, ?array $signature = null)
    {
        $symbol   = $param['symbol']; // 账户类型 USDT/NOX
        $amount   = $param['amount'];
        $real_am  = $param['real_am'];
        $fee      = $param['fee'];
        $fee_rate = $param['fee_rate'];
        $mode     = $param['withdraw_mode']; // 提现模式
        $is_audit = $param['withdraw_mode'] == Withdraw::WITHDRAW_MODE_AUDIT ? 1 : 0;

        // 冻结余额
        WalletLog::addLog($user->id, $amount, $symbol, WalletLog::CHANGE_TYPE_FREEZE, WalletLog::TYPE_WITHDRAW_FREEZE);

        // 创建订单
        $order                = new Withdraw();
        $order->sn            = randOrderNo();
        $order->user_id       = $user->id;
        $order->symbol        = $symbol;
        $order->amount        = $amount;
        $order->real_am       = $real_am;
        $order->fee           = $fee; // 手续费
        $order->fee_rate      = $fee_rate; // 手续费率
        $order->withdraw_mode = $mode;
        $order->to_addr       = $user->address; // 用户钱包地址
        $order->status        = $is_audit ? Withdraw::STATUS_AUDIT : Withdraw::STATUS_PENDING;

        if ($signature && $mode == Withdraw::WITHDRAW_MODE_CONTRACT) {
            $order->deadline          = $signature['deadline']; // 签名过期时间
            $order->project_signer    = $signature['project_signer']; // 项目方签名地址
            $order->signature         = $signature['sign']; // 签名
            $order->message           = $signature['message']; // 签名信息
            $order->withdraw_contract = $signature['withdraw_contract']; // 提现合约地址
        }

        $order->save();

        return $order;
    }

    /**
     * 审核
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function audit(Withdraw $order)
    {
        $now = Carbon::now();
        $res = Withdraw::query()->where('id', $order->id)->where('status', Withdraw::STATUS_AUDIT)->update([
            'status'   => Withdraw::STATUS_PENDING,
            'audit_at' => $now,
        ]);
        if (! $res) {
            Log::error('withdraw_audit_callback_error', ['订单状态异常', $order->sn]);
            throw new LogicException(__('订单状态异常'));
        }
    }

    /**
     * 审核驳回/失败驳回
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function reject(Withdraw $order)
    {
        $now = Carbon::now();
        if ($order->status == Withdraw::STATUS_AUDIT) {
            $res = Withdraw::query()->where('id', $order->id)->where('status', Withdraw::STATUS_AUDIT)->update([
                'status'   => Withdraw::STATUS_REJECT,
                'audit_at' => $now,
                'back_am'  => $order->amount,
                'is_back'  => 1,
                'back_at'  => $now,
            ]);
        } else {
            $res = Withdraw::query()->where('id', $order->id)->where('status', Withdraw::STATUS_FAIL)->update([
                'status'  => Withdraw::STATUS_REJECT,
                'back_am' => $order->amount,
                'is_back' => 1,
                'back_at' => $now,
            ]);
        }
        if (! $res) {
            Log::error('withdraw_reject_callback_error', ['订单状态异常', $order->sn]);
            throw new LogicException(__('订单状态异常'));
        }

        // 返还冻结金额
        WalletLog::addLog(
            $order->user_id,
            $order->amount,
            $order->symbol,
            WalletLog::CHANGE_TYPE_BACK,
            WalletLog::TYPE_WITHDRAW_BACK
        );
    }

    /**
     * 提现成功回调
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function doneCallback(Withdraw $order, ?string $hash = null)
    {
        $res = Withdraw::query()->where('id', $order->id)->where('status', Withdraw::STATUS_PENDING)->update([
            'hash'    => $hash,
            'status'  => Withdraw::STATUS_DONE,
            'done_at' => Carbon::now(),
        ]);
        if (! $res) {
            Log::error('withdraw_done_callback_error', ['订单状态异常', $order->sn]);
            throw new LogicException(__('订单状态异常'));
        }

        // 扣除冻结金额
        WalletLog::addLog(
            $order->user_id,
            $order->amount,
            $order->symbol,
            WalletLog::CHANGE_TYPE_OUT,
            WalletLog::TYPE_WITHDRAW_OUT
        );

        // 手续费进当日 fees 池（供节点池分红）；仅 NOX 手续费入池，USDT 手续费不进池
        if ($order->symbol === WalletLog::SYMBOL_NOX && bccomp($order->fee, '0', 6) > 0) {
            // $date = Carbon::today()->toDateString();
            // $re = DB::table('fees')->where('date', $date)->update([
            //     'amount' => DB::raw('amount + ' . $order->fee),
            //     'updated_at' => now(),
            // ]);
            // if (! $re) {
            //     DB::table('fees')->insert([
            //         'date' => $date,
            //         'amount' => $order->fee,
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]);
            // }
            FeeLog::addLog($order->user_id, $order->fee, FeeLog::TYPE_WITHDRAW);
        }
    }

    /**
     * 提现超时取消回调
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function cancelCallback(Withdraw $order)
    {
        $now = Carbon::now();
        $res = Withdraw::query()->where('id', $order->id)->where('status', Withdraw::STATUS_PENDING)->update([
            'status'  => Withdraw::STATUS_REJECT,
            'fail_at' => $now,
            'is_back' => 1,
            'back_at' => $now,
        ]);
        if (! $res) {
            Log::error('withdraw_cancel_callback_error', ['订单状态异常', $order->sn]);
            throw new LogicException(__('订单状态异常'));
        }

        // 返还冻结金额
        WalletLog::addLog(
            $order->user_id,
            $order->amount,
            $order->symbol,
            WalletLog::CHANGE_TYPE_BACK,
            WalletLog::TYPE_WITHDRAW_BACK
        );
    }
}
