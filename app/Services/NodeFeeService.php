<?php

namespace App\Services;

use App\Models\NodeFeeAllocation;
use App\Models\NodeFeeLog;
use App\Models\UserData;
use App\Models\WalletLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NodeFeeService
{
    private bool $isLog = true;

    public function __construct(bool $isLog = true)
    {
        $this->isLog = $isLog;
    }

    public function run(string $settle_date, bool $skip = false): bool
    {
        $lock = Cache::lock('node_fee_service', 1800);
        if (! $lock->get()) {
            throw new \Exception('任务运行中，请稍后再试');
        }

        $failures = 0;

        try {
            if (! $skip && NodeFeeAllocation::query()
                ->where('sett_date', $settle_date)
                ->where('status', NodeFeeAllocation::STATUS_DONE)
                ->exists()) {
                $lock->release();

                return true;
            }

            $fee = DB::table('fees')->where('date', $settle_date)->value('amount');
            if (! $fee || bccomp($fee, '0', 6) <= 0) {
                $lock->release();

                return true;
            }

            $alloc = NodeFeeAllocation::query()->where('sett_date', $settle_date)->first();
            if (! $alloc) {
                $alloc = NodeFeeAllocation::create([
                    'sett_date'   => $settle_date,
                    'total_fee'   => $fee,
                    'node_fee_am' => '0.000000',
                    'node_count'  => 0,
                    'per_share'   => '0.000000',
                    'project_am'  => '0.000000',
                    'remain'      => '0.000000',
                    'status'      => NodeFeeAllocation::STATUS_RUNNING,
                ]);
            }

            // 节点池 = 当日手续费 × 节点分红比例（默认90%）；其余部分不再进项目钱包，直接计入 remain
            $node_fee_rate = getGlobalConfig('alpha_node_fee_rate', '90');
            $node_fee_am   = bcround2(bcdiv(bcmul($fee, $node_fee_rate, 8), '100', 8), 6);

            // 权重 = user_datas.node_num（持节点即计权，与订单条数无关）
            $holders = UserData::query()
                ->where('node_num', '>', 0)
                ->orderBy('user_id')
                ->get(['user_id', 'node_num']);
            $total_num = 0;
            foreach ($holders as $h) {
                $total_num += (int) $h->node_num;
            }

            if ($total_num <= 0) {
                // 无持节点用户：节点池 + 其余份额全额留在 remain，直接结束
                $alloc->update([
                    'node_fee_am' => $node_fee_am,
                    'node_count'  => 0,
                    'per_share'   => '0.000000',
                    'project_am'  => '0.000000',
                    'remain'      => $fee,
                    'status'      => NodeFeeAllocation::STATUS_DONE,
                ]);
                $lock->release();

                return true;
            }

            // 每节点等额一份，尾差并入 remain
            $per_node = bcfloor2(bcdiv($node_fee_am, (string) $total_num, 8), 6);
            $this->addLog(
                '日期: %s 手续费: %s 节点池: %s 节点数: %s 每节点分红: %s',
                'INFO',
                $settle_date,
                removeTrailingZeros($fee),
                removeTrailingZeros($node_fee_am),
                $total_num,
                removeTrailingZeros($per_node),
            );

            foreach ($holders as $h) {
                if (NodeFeeLog::query()->where('alloc_id', $alloc->id)->where('user_id', $h->user_id)->exists()) {
                    continue;
                }
                $user_share = bcround2(bcmul($per_node, (string) $h->node_num, 8), 6);
                if (bccomp($user_share, '0', 6) <= 0) {
                    continue;
                }
                try {
                    $this->addLog(
                        '日期: %s 用户: %s 节点数: %s 分红: %s',
                        'INFO',
                        $settle_date,
                        $h->user_id,
                        $h->node_num,
                        removeTrailingZeros($user_share),
                    );
                    DB::transaction(function () use ($settle_date, $alloc, $h, $user_share, $per_node, $total_num, $node_fee_am) {
                        AlphaQuotaService::creditEarnings($h->user_id, $user_share, WalletLog::TYPE_NODE_FEE_IN);
                        NodeFeeLog::create([
                            'sett_date' => $settle_date,
                            'alloc_id'  => $alloc->id,
                            'user_id'   => $h->user_id,
                            'am'        => $user_share,
                            'node_num'  => (int) $h->node_num,
                            'total_num' => $total_num,
                            'total_am'  => $node_fee_am,
                            'per_share' => $per_node,
                        ]);
                    });
                } catch (\Throwable $e) {
                    Log::error('node_fee_credit_error', [$h->user_id, $settle_date, $e->getMessage()]);
                    $failures++;
                }
            }

            // 收尾：remain = 当日手续费 − 已实际发放给持节点用户的金额（含 10% 份额与取整尾差）
            $paid   = bcadd((string) (NodeFeeLog::query()->where('alloc_id', $alloc->id)->sum('am') ?: 0), '0', 6);
            $remain = bcsub($fee, $paid, 6);
            $alloc->update([
                'node_fee_am' => $node_fee_am,
                'node_count'  => $total_num,
                'per_share'   => $per_node,
                'project_am'  => '0.000000',
                'remain'      => $remain,
                'status'      => NodeFeeAllocation::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            Log::error('node_fee_settle_error', [$settle_date, $e->getMessage(), $e->getTraceAsString()]);
            $lock->release();

            return false;
        }

        $lock->release();

        return $failures === 0;
    }

    protected function addLog(string $msg, string $tag = 'INFO', mixed ...$args)
    {
        if ($this->isLog) {
            echo '['.date('Y-m-d H:i:s')."] [$tag] ".sprintf($msg, ...$args).PHP_EOL;
        }
    }
}
