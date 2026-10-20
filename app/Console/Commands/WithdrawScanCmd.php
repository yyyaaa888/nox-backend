<?php

namespace App\Console\Commands;

use App\Exceptions\LogicException;
use App\Exceptions\TimestampException;
use App\Models\Chain;
use App\Models\Withdraw;
use App\Services\Web3\WithdrawContractService;
use App\Services\WithdrawOrderService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WithdrawScanCmd extends Command
{
    protected $signature = 'app:withdraw-scan';

    protected $description = '提现补单扫描';

    const TRY_TIMES = 3;

    /**
     * @var array<string,WithdrawContractService>
     */
    protected array $services;

    public function handle()
    {
        $lock = Cache::lock('withdraw_scan_cmd', 60); // lock for 60 seconds
        if (! $lock->get()) {
            $this->error(date('[Y-m-d H:i:s] ').'提现补单执行中');

            return;
        }

        // $this->info('提现补单开始...');

        $chain_id = config('site.asset.chain_id'); // 区块链id
        $chain    = Chain::getInfoByChainId($chain_id);
        if (empty($chain)) {
            $this->error(date('[Y-m-d H:i:s] ').'未找到BSC链信息');
            $lock->release();

            return;
        }

        try {
            // 处理[24小时, 6分钟前]的订单
            $start_time = Carbon::now()->subHours(24);
            $end_time   = Carbon::now()->subMinutes(6);

            /** @var Withdraw[]|Collection $list */
            $list = Withdraw::query()
                ->where('status', Withdraw::STATUS_PENDING)
                ->where('withdraw_mode', 3)
                ->whereBetween('created_at', [$start_time, $end_time])
                ->where('try_num', '<', self::TRY_TIMES)
                ->orderBy('try_num', 'asc')
                ->limit(100)
                ->get();

            if ($list->isEmpty()) {
                $lock->release();

                return;
            }

            $total                  = 0;
            $ok_num                 = 0;
            $fail_num               = 0;
            $withdraw_order_service = new WithdrawOrderService();
            foreach ($list as $item) {

                $service = $this->getService($chain);

                $total++;
                // $this->info(date('[Y-m-d H:i:s] ') . "处理提现订单: {$item->sn} 已重试次数: {$item->try_num}");
                $ret = $service->usedSignature($item->signature);
                // $this->info("提现订单 {$item->sn} : " . json_encode($ret));
                $item->try_num += 1;
                if ($ret === true) {
                    DB::beginTransaction();
                    $withdraw_order_service->doneCallback($item);
                    DB::commit();
                    $ok_num++;
                    $this->info(date('[Y-m-d H:i:s] ')."提现订单 {$item->sn} 提现成功");
                } else {
                    if ($item->try_num < self::TRY_TIMES) {
                        $item->save();
                    } else {
                        $item->save();
                        DB::beginTransaction();
                        try {
                            $withdraw_order_service->cancelCallback($item);
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->error(date('[Y-m-d H:i:s] ')."提现订单 {$item->sn} 提现失败, 返还异常: ".$e->getMessage());

                            continue;
                        }
                        $fail_num++;
                        $this->error(date('[Y-m-d H:i:s] ')."提现订单 {$item->sn} 提现失败");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error(date('[Y-m-d H:i:s] ').'提现补单异常:'.$e->getMessage());
            $lock->release();

            return;
        }

        if ($total > 0) {
            $this->info('['.date('Y-m-d H:i:s')."] 提现补单结束 总数: {$total} 成功: {$ok_num} 失败: {$fail_num}");
        }
        $lock->release();

        return 0;
    }

    protected function getService(Chain $chain): WithdrawContractService
    {
        $chain_id = $chain->chain_id;
        if (isset($this->services[$chain_id])) {
            return $this->services[$chain_id];
        }

        $withdraw_contract = config('site.withdraw_contract');
        if (empty($withdraw_contract)) {
            throw new LogicException('提现合约地址为空');
        }

        $abi_path = config('site.withdraw_abi_path');
        $abi      = file_get_contents(storage_path($abi_path));

        $service = new WithdrawContractService([
            'rpc'     => $chain->rpc_url,
            'abi'     => $abi,
            'address' => $withdraw_contract,
        ]);
        // 获取时间戳
        $timestamp = $service->getCurrentTimestamp();
        $sub_time  = abs(Carbon::now()->timestamp - $timestamp);
        if ($sub_time > 30) {
            throw new TimestampException("当前时间差异 {$sub_time} 秒，超过30秒");
        }
        $this->services[$chain_id] = $service;

        return $service;
    }
}
