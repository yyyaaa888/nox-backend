<?php

namespace App\Services;

use App\Exceptions\LogicException;
use App\Models\AlphaOrder;
use App\Models\AlphaReward;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlphaRewardService
{
    private bool $isLog = true;

    public function __construct(bool $isLog = true)
    {
        $this->isLog = $isLog;
    }

    public function run(string $settle_date, bool $skip = false): bool
    {
        $lock = Cache::lock('alpha_reward_service', 1800);
        if (! $lock->get()) {
            throw new \Exception('任务运行中，请稍后再试');
        }

        $endOfDay = Carbon::parse($settle_date)->endOfDay();
        $total    = AlphaOrder::query()
            ->where('status', AlphaOrder::STATUS_PAID)
            ->where('created_at', '<=', $endOfDay)
            ->count();
        $settled = AlphaReward::query()->where('settle_date', $settle_date)->count();

        if (! $skip && $total > 0 && $settled >= $total) {
            $lock->release();

            return true;
        }

        try {
            // 获取NOX资产汇率
            $asset       = $this->getTokenAsset();
            $token_price = $asset->exchange_rate;
        } catch (LogicException $e) {
            $lock->release();
            throw $e;
        } catch (\Throwable $e) {
            $lock->release();
            throw $e;
        }

        $failures = 0;

        try {
            AlphaOrder::query()
                ->where('status', AlphaOrder::STATUS_PAID)
                ->where('created_at', '<=', $endOfDay)
                ->chunkById(100, function ($orders) use ($settle_date, $token_price, &$failures) {
                    foreach ($orders as $order) {
                        if (AlphaReward::query()->where('order_id', $order->id)->where('settle_date', $settle_date)->exists()) {
                            continue;
                        }
                        try {
                            $this->settleOne($order, $settle_date, $token_price);
                        } catch (\Throwable $e) {
                            $failures++;
                            Log::error('alpha_reward_settle_order_error', [
                                'order_id'    => $order->id,
                                'settle_date' => $settle_date,
                                'error'       => $e->getMessage(),
                                'trace'       => $e->getTraceAsString(),
                            ]);
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::error('alpha_reward_settle_error', [$settle_date, $e->getMessage(), $e->getTraceAsString()]);
            $lock->release();

            return false;
        }

        $lock->release();

        return $failures > 0 ? false : true;
    }

    private function settleOne(AlphaOrder $order, string $settle_date, string $token_price): void
    {
        DB::transaction(function () use ($order, $settle_date, $token_price) {
            $product = $order->product;
            if (! $product) {
                throw new LogicException("订单 {$order->id} 产品 {$order->product_id} 不存在", 1001);
            }
            $userData = UserData::getInfo($order->user_id);
            if (! $userData) {
                throw new LogicException("订单 {$order->id} 用户 {$order->user_id} 用户数据不存在", 1002);
            }

            // 有效月产率：持节点用户用 product.node_rate，否则用product.base_rate
            if ($userData->node_id) {
                $rate = $product->node_rate;
            } else {
                $rate = $product->base_rate;
            }
            // 产出(USDT) = 本金 * 月产率 / 3000，受 rem_reward 封顶（保留8位小数，落库时由 decimal(20,6) 进位）
            $reward_u = bcdiv(bcmul($order->amount, $rate, 18), '3000', 8);
            if (bccomp($reward_u, $order->rem_reward, 8) > 0) {
                $reward_u = $order->rem_reward;
            }
            if (bccomp($reward_u, '0', 8) <= 0) {
                return;
            }
            // 产出(NOX) = 产出(USDT) / 汇率
            $reward = bcdiv($reward_u, $token_price, 8);
            if (bccomp($reward, '0', 8) <= 0) {
                return;
            }

            $res = AlphaOrder::query()
                ->where('id', $order->id)
                ->where('status', AlphaOrder::STATUS_PAID)
                ->where('rem_reward', '>=', $reward)
                ->update([
                    'rem_reward'  => DB::raw('rem_reward - '.$reward),
                    'rel_reward'  => DB::raw('rel_reward + '.$reward),
                    'last_reward' => $reward,
                    'last_day'    => $settle_date,
                ]);
            if (! $res) {
                throw new LogicException("订单 {$order->id} 结算冲突");
            }

            [$available, $frozen] = array_values(AlphaQuotaService::creditEarnings($order->user_id, $reward, WalletLog::TYPE_ALPHA_REWARD_IN));

            AlphaReward::create([
                'user_id'       => $order->user_id,
                'order_id'      => $order->id,
                'order_am'      => $order->amount,
                'rate'          => $rate,
                'reward_am'     => $reward,
                'quota_used_am' => $available,
                'frozen_am'     => $frozen,
                'settle_date'   => $settle_date,
            ]);

            $this->addLog(
                '日期: %s 订单: %s 订单金额: %s 产出(NOX): %s 剩余: %s 月产率: %s 产出(USDT): %s 已使用额度: %s 已冻结额度: %s',
                'INFO',
                $settle_date,
                $order->id,
                removeTrailingZeros($order->amount),
                removeTrailingZeros($reward),
                removeTrailingZeros($order->rem_reward),
                removeTrailingZeros($rate),
                removeTrailingZeros($reward_u),
                removeTrailingZeros($available),
                removeTrailingZeros($frozen),
            );

            // 更新用户收益数据
            UserData::query()->where('user_id', $order->user_id)->update([
                'stake_reward'   => DB::raw('stake_reward + '.$reward),
            ]);

            $fresh = AlphaOrder::find($order->id);
            if (bccomp($fresh->rel_reward, $fresh->quota_reward, 6) >= 0) {
                AlphaOrder::query()->where('id', $order->id)->update([
                    'status'  => AlphaOrder::STATUS_DONE,
                    'done_at' => Carbon::now(),
                ]);
                // 订单完成：扣除对应质押金额 + 释放额度槽
                UserData::query()->where('user_id', $order->user_id)->where('current_slot', '>=', $order->amount)->update([
                    'curr_stake'   => DB::raw('curr_stake - '.$order->amount),
                    'current_slot' => DB::raw('current_slot - '.$order->amount),
                ]);
            }

            // 团队收益 + 平级奖：下线产生静态收益，向推荐链分发
            \App\Services\RewardService::distributeTeam($order, $settle_date, $reward);
        });
    }

    private function getTokenAsset(): Asset
    {
        $asset_code = Asset::CODE_NOX;
        $chain_id   = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException('区块链id未配置');
        }
        $asset = Asset::getPayInfo($asset_code, $chain_id);
        if (! $asset) {
            throw new LogicException(sprintf('资产(%s)未配置', $asset_code));
        }
        if (bccomp($asset->exchange_rate, '0', 8) <= 0) {
            throw new LogicException(sprintf('资产(%s)汇率未配置', $asset_code));
        }

        return $asset;
    }

    protected function addLog(string $msg, string $tag = 'INFO', mixed ...$args)
    {
        if ($this->isLog) {
            echo '['.date('Y-m-d H:i:s')."] [$tag] ".sprintf($msg, ...$args).PHP_EOL;
        }
    }
}
