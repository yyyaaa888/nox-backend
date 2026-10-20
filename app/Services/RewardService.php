<?php

namespace App\Services;

use App\Models\AlphaOrder;
use App\Models\InviteReward;
use App\Models\RewardRate;
use App\Models\TeamReward;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService
{
    const MAX_LAYER = 5;

    /**
     * 推广收益：下线购买资产包时，沿推荐链按层向各上级抽成
     */
    public static function distributeInvite(AlphaOrder $order)
    {
        if ($order->status !== AlphaOrder::STATUS_PAID) {
            return;
        }
        $downlineId = $order->user_id;
        $ancestors  = UserRelationClosure::getAncestors($downlineId);
        foreach ($ancestors as $rel) {
            $layer    = $rel->depth;
            $anc      = User::getInfo($rel->ancestor_id);
            $userData = UserData::getInfo($rel->ancestor_id);
            if (! $anc || ! $userData || $anc->level_id <= 0) {
                continue;
            }
            // 逐上级幂等：该上级已发放则跳过（重跑只补未发放的上级）
            if (InviteReward::query()->where('order_id', $order->id)->where('user_id', $anc->id)->exists()) {
                continue;
            }
            $hasNode = $userData->node_id ? 1 : 0;
            $rate    = RewardRate::query()
                ->where('type', RewardRate::TYPE_INVITE)
                ->where('rank', $anc->level_id)
                ->where('has_node', $hasNode)
                ->where('layer', $layer)
                ->first()?->rate;
            if ($rate === null || bccomp($rate, '0', 2) <= 0) {
                continue;
            }
            $am_u = bcdiv(bcmul($order->amount, $rate, 18), '100', 6);
            if (bccomp($am_u, '0', 6) <= 0) {
                continue;
            }
            $am = bcdiv($am_u, $order->token_price, 6);
            if (bccomp($am, '0', 6) <= 0) {
                continue;
            }
            try {
                DB::transaction(function () use ($anc, $order, $downlineId, $layer, $hasNode, $am, $rate) {
                    AlphaQuotaService::creditEarnings($anc->id, $am, WalletLog::TYPE_INVITE_REWARD);
                    InviteReward::create([
                        'user_id'     => $anc->id,
                        'child_id'    => $downlineId,
                        'order_id'    => $order->id,
                        'level'       => $anc->level_id,
                        'layer'       => $layer,
                        'has_node'    => $hasNode,
                        'order_am'    => $order->amount,
                        'reward_am'   => $am,
                        'reward_rate' => $rate,
                    ]);
                    UserData::query()->where('user_id', $anc->id)->update([
                        'invite_reward' => DB::raw('invite_reward + '.$am),
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('reward_invite_error', [$anc->id, $order->id, $e->getMessage()]);
            }
        }
    }

    /**
     * 团队收益 + 平级奖：下线产生静态收益时，沿推荐链按层向各上级抽成。
     * 等级门槛：上级低于下级不发；同级只发平级奖（团队奖 × 10%）；上级高于下级全额发放。
     */
    public static function distributeTeam(AlphaOrder $order, string $settle_date, string $static_reward)
    {
        if ($order->status !== AlphaOrder::STATUS_PAID) {
            return;
        }
        $downlineId    = $order->user_id;
        $downlineLevel = User::query()->where('id', $downlineId)->value('level_id') ?: 0;
        $ancestors     = UserRelationClosure::getAncestors($downlineId);
        foreach ($ancestors as $rel) {
            $layer    = $rel->depth;
            $anc      = User::getInfo($rel->ancestor_id);
            $userData = UserData::getInfo($rel->ancestor_id);
            if (! $anc || ! $userData || $anc->level_id <= 0) {
                continue;
            }
            // 逐上级幂等：该上级当日已发放则跳过（重跑只补未发放的上级）
            if (TeamReward::query()->where('order_id', $order->id)->where('sett_date', $settle_date)->where('user_id', $anc->id)->exists()) {
                continue;
            }
            $hasNode = $userData->node_id ? 1 : 0;
            // 规则：上级低于下级 → 不发团队奖励
            if ($anc->level_id < $downlineLevel) {
                continue;
            }
            $rate = RewardRate::query()
                ->where('type', RewardRate::TYPE_TEAM)
                ->where('rank', $anc->level_id)
                ->where('has_node', $hasNode)
                ->where('layer', $layer)
                ->value('rate');
            if ($rate === null || bccomp($rate, '0', 2) <= 0) {
                continue;
            }
            $teamAm = bcround2(bcdiv(bcmul($static_reward, $rate, 8), '100', 8), 6);
            if (bccomp($teamAm, '0', 6) <= 0) {
                continue;
            }
            // 同级 → 只发平级奖（团队奖 × 10%），记为 TYPE_SAME_LEVEL_REWARD；高于下级 → 全额团队奖
            $isSame   = ($anc->level_id == $downlineLevel) ? 1 : 0;
            $creditAm = $isSame
                ? bcround2(bcdiv(bcmul($teamAm, '10', 8), '100', 8), 6)
                : $teamAm;
            if (bccomp($creditAm, '0', 6) <= 0) {
                continue;
            }
            $creditType = $isSame ? WalletLog::TYPE_SAME_LEVEL_REWARD : WalletLog::TYPE_TEAM_REWARD;
            try {
                DB::transaction(function () use ($anc, $order, $downlineId, $downlineLevel, $settle_date, $static_reward, $layer, $hasNode, $isSame, $creditAm, $creditType, $rate) {
                    AlphaQuotaService::creditEarnings($anc->id, $creditAm, $creditType);
                    TeamReward::create([
                        'user_id'       => $anc->id,
                        'child_id'      => $downlineId,
                        'order_id'      => $order->id,
                        'level'         => $anc->level_id,
                        'prev_level'    => 0,
                        'child_level'   => $downlineLevel,
                        'layer'         => $layer,
                        'has_node'      => $hasNode,
                        'is_same_level' => $isSame,
                        'order_am'      => $static_reward,
                        'reward_am'     => $creditAm,
                        'reward_rate'   => $rate,
                        'sett_date'     => $settle_date,
                    ]);
                    UserData::query()->where('user_id', $anc->id)->update([
                        'team_reward' => DB::raw('team_reward + '.$creditAm),
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('reward_team_error', [$anc->id, $order->id, $settle_date, $e->getMessage()]);
            }
        }
    }
}
