<?php

namespace App\Services;

use App\Models\AlphaOrder;
use App\Models\NodeOrder;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;

class AlphaQuotaService
{
    /**
     * 当前额度槽 S = 2.5 * (进行中订单本金之和)
     */
    public static function currentSlot(int $uid): string
    {
        // $alphaSum = AlphaOrder::query()
        //     ->where('user_id', $uid)
        //     ->where('status', AlphaOrder::STATUS_PAID)
        //     ->sum('amount');
        // $nodeSum = NodeOrder::query()
        //     ->where('user_id', $uid)
        //     ->where('status', NodeOrder::STATUS_PAID)
        //     ->sum('amount');
        // $sum = bcadd($alphaSum, $nodeSum, 6);
        $sum      = UserData::getInfo($uid)->current_slot;
        $multiple = getGlobalConfig('alpha_quota_multiple', '2.5');

        return bcadd(bcmul($sum, $multiple, 8), '0', 6);
    }

    /**
     * 产出入账：C+E <= S 全入可用；超出部分进冻结。返回 [available, frozen]。
     */
    public static function creditEarnings(int $uid, string $amount, int $type): array
    {
        if (bccomp($amount, '0', 6) <= 0) {
            return ['available' => '0.000000', 'frozen' => '0.000000'];
        }

        return DB::transaction(function () use ($uid, $amount, $type) {
            $slot = self::currentSlot($uid);
            $data = UserData::getInfo($uid);
            if (! $data) {
                $data = UserData::addInfo($uid);
            }
            $used   = $data->alpha_quota_used;
            $remain = bcsub($slot, $used, 6);

            $available = '0.000000';
            $frozen    = '0.000000';
            if (bccomp($remain, '0', 6) > 0) {
                $available = bccomp($amount, $remain, 6) > 0 ? $remain : $amount;
                $frozen    = bcsub($amount, $available, 6);
                WalletLog::addLog($uid, $available, WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, $type);
                $newUsed = bcadd($used, $available, 6);
                UserData::query()->where('user_id', $uid)->update([
                    'alpha_quota_used' => $newUsed,
                    'alpha_frozen'     => DB::raw('alpha_frozen + '.$frozen),
                ]);
            } else {
                // 槽已满：全额冻结
                $frozen = $amount;
                UserData::query()->where('user_id', $uid)->update([
                    'alpha_frozen' => DB::raw('alpha_frozen + '.$amount),
                ]);
            }

            return ['available' => $available, 'frozen' => $frozen];
        });
    }

    /**
     * 新订单后解冻：把冻结收益按新槽位余量转可用。返回解冻金额。
     *
     * 槽位守恒：可用收益终身占用 alpha_quota_used(C)，超槽部分暂存 alpha_frozen(F)；
     * 槽位扩大后放行 F 入可用，等同该部分此刻占用了新增容量，故须同步 C + sweep，
     * 否则同一容量会被重复使用，用户累计可用收益会突破 2.5 倍上限。
     */
    public static function sweep(int $uid): string
    {
        return DB::transaction(function () use ($uid) {
            $data = UserData::getInfo($uid);
            if (! $data) {
                return '0.000000';
            }
            $frozen = $data->alpha_frozen;
            if (bccomp($frozen, '0', 6) <= 0) {
                return '0.000000';
            }
            $slot     = self::currentSlot($uid);
            $headroom = bcsub($slot, $data->alpha_quota_used, 6);
            if (bccomp($headroom, '0', 6) <= 0) {
                return '0.000000';
            }
            $sweep = bccomp($frozen, $headroom, 6) > 0 ? $headroom : $frozen;
            $re    = UserData::query()
                ->where('user_id', $uid)
                ->where('alpha_frozen', '>=', $sweep)
                ->update([
                    'alpha_frozen'     => DB::raw('alpha_frozen - '.$sweep),
                    'alpha_quota_used' => DB::raw('alpha_quota_used + '.$sweep),
                ]);
            if (! $re) {
                return '0.000000';
            }
            WalletLog::addLog($uid, $sweep, WalletLog::SYMBOL_NOX, WalletLog::CHANGE_TYPE_IN, WalletLog::TYPE_ALPHA_UNFREEZE);

            return $sweep;
        });
    }
}
