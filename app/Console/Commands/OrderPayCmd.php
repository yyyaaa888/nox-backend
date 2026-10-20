<?php

namespace App\Console\Commands;

use App\Exceptions\LogicException;
use App\Exceptions\PayFailException;
use App\Models\Asset;
use App\Models\Chain;
use App\Models\PayOrder;
use App\Models\UserRecharge;
use App\Services\UserRechargeService;
use App\Services\Web3\PaymentContractService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPayCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order-pay {--limit=50 : 每次处理的订单数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查待支付订单状态，通过合约查询支付信息';

    /**
     * @var PaymentContractService[]
     */
    private array $payServices = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $this->info(date('[Y-m-d H:i:s] ') . '开始检查订单支付状态...');

        try {
            [$asset, $chain, $pay_contract] = $this->getAsset(Asset::CODE_USDT);
        } catch (LogicException $e) {
            $this->error(date('[Y-m-d H:i:s] ').$e->getMessage());

            return Command::FAILURE;
        }

        $limit = $this->option('limit');

        try {
            // 处理[24小时, 1分钟前]的订单
            $start_time = Carbon::now()->subHours(24);
            $end_time   = Carbon::now()->subMinute();
            // 获取待支付订单
            $pendingOrders = PayOrder::query()
                ->whereBetween('created_at', [$start_time, $end_time])
                ->where('status', PayOrder::STATUS_WAIT)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            if ($pendingOrders->isEmpty()) {
                // $this->info(date('[Y-m-d H:i:s] ') . '没有待处理的订单');
                return Command::SUCCESS;
            }
            // $this->info(date('[Y-m-d H:i:s] ') . "找到 {$pendingOrders->count()} 个待支付订单");

            $processedCount = 0;
            $successCount   = 0;
            $timeoutCount   = 0;
            $failCount      = 0;

            foreach ($pendingOrders as $order) {
                $processedCount++;
                $result = $this->processOrder($order);
                if ($result === 'success') {
                    $successCount++;
                } elseif ($result === 'timeout') {
                    $timeoutCount++;
                } elseif ($result === 'fail') {
                    $failCount++;
                }

                // 避免请求过于频繁
                usleep(100000); // 0.1秒延迟
            }

            if ($processedCount > 0) {
                $this->info(date('[Y-m-d H:i:s] ')."处理完成: 总计 {$processedCount} 个，成功 {$successCount} 个，超时 {$timeoutCount} 个，失败 {$failCount} 个");
            }
        } catch (\Throwable $e) {
            $this->error(date('[Y-m-d H:i:s] ')."处理订单时发生错误: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 处理单个订单
     */
    public function processOrder(PayOrder $order)
    {
        try {
            DB::beginTransaction();
            // $this->info(date('[Y-m-d H:i:s] ') . "检查订单: {$order->sn}");

            // 检查订单是否超过5分钟
            $createdAt      = Carbon::parse($order->created_at);
            $now            = Carbon::now();
            $minutesElapsed = $createdAt->diffInMinutes($now);
            // 调用合约获取支付信息
            $payInfo = $this->getPayInfoFromContract($order);
            if (! $payInfo) {
                throw new \Exception("无法获取订单 {$order->sn} 的支付信息");
            }
            // 检查是否已支付
            $result = 'wait';
            if ($payInfo['isPaid']) {
                // 验证支付信息
                $this->validatePayment($order, $payInfo);
                // 完成订单
                $this->completeOrder($order, $payInfo);
                $this->info(date('[Y-m-d H:i:s] ')."订单 {$order->sn} 支付成功");
                $result = 'success';
            } else {
                // 检查是否超时(超过5分钟)
                if ($minutesElapsed >= 5) {
                    $this->timeoutOrder($order);
                    $this->warn(date('[Y-m-d H:i:s] ')."订单 {$order->sn} 超时取消");
                    $result = 'timeout';
                } else {
                    // $this->line("订单 {$order->sn} 未支付，等待中... ({$minutesElapsed}/5 分钟)");
                }
            }
            DB::commit();

            // pay after
            if ($result === 'success') {
                try {
                    $this->payAfter($order);
                } catch (\Throwable $e) {
                    Log::error('orderpay_after_error', [$order->sn, $e->getMessage()]);
                }
            }

            return $result;
        } catch (PayFailException $e) {
            $this->failOrder($order, $e);
            $this->error(date('[Y-m-d H:i:s] ')."订单 {$order->sn} 支付校验失败: {$e->getMessage()}");
            DB::commit();

            return 'failed';
        } catch (\Throwable $e) {
            $this->error(date('[Y-m-d H:i:s] ')."处理订单 {$order->sn} 时发生错误: {$e->getMessage()}");
            DB::rollBack();

            return 'failed';
        }
    }

    /**
     * 从合约获取支付信息
     */
    private function getPayInfoFromContract(PayOrder $payOrder): ?array
    {
        if (! config('app.check.payOrder')) {
            return [
                'orderId'         => $payOrder->id,
                'contractAddress' => $payOrder->token_addr,
                'fromAddress'     => $payOrder->pay_addr,
                'toAddress'       => $payOrder->dest_addr,
                'payTotal'        => $payOrder->amount,
                'payTime'         => $payOrder->created_at->timestamp,
                'isPaid'          => true,
            ];
        }
        $payService = $this->getPayService($payOrder->symbol);
        $sn         = $payOrder->sn;
        try {
            // 调用合约
            $result = $payService->getPayInfo($sn);
            if (! $result) {
                $this->warn(date('[Y-m-d H:i:s] ').'合约返回空数据');

                return null;
            }
            if (! empty($result['payTotal'])) {
                $result['payTotal'] = bcdiv($result['payTotal'], pow(10, $payOrder->decimals), $payOrder->decimals);
            }
            if (! empty($result['payTime'])) {
                $result['payTime'] = date('Y-m-d H:i:s', $result['payTime']);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->error(date('[Y-m-d H:i:s] ')."getPayInfoFromContract异常: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * 完成订单支付
     */
    private function completeOrder(PayOrder $order, array $payInfo)
    {
        $hash     = $payInfo['hash'] ?? null;
        $payTotal = $payInfo['payTotal'] ?? null;
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payCallback($bus_order, $order, $hash, $payTotal);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }

    // pay after
    private function payAfter(PayOrder $order)
    {
        if ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Jobs\UserUpgradeJob::dispatch($bus_order->user_id);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Jobs\UserUpgradeJob::dispatch($bus_order->user_id);
            }
        }
    }

    /**
     * 处理订单支付失败
     */
    private function failOrder(PayOrder $order, PayFailException $error)
    {
        $fail_reason = '支付校验失败: '.$error->getMessage();
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payFail($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payFail($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payFail($bus_order, $order, $fail_reason);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }

    /**
     * 订单超时处理
     */
    private function timeoutOrder(PayOrder $order)
    {
        $fail_reason = '超时取消';
        if ($order->type == PayOrder::TYPE_RECHARGE) {
            $bus_order = UserRecharge::getBySn($order->sn);
            if ($bus_order) {
                UserRechargeService::payTimeout($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_ALPHA_BUY) {
            $bus_order = \App\Models\AlphaOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\AlphaOrderService::payTimeout($bus_order, $order, $fail_reason);
            }
        } elseif ($order->type == PayOrder::TYPE_NODE_BUY) {
            $bus_order = \App\Models\NodeOrder::getBySn($order->sn);
            if ($bus_order) {
                \App\Services\NodeBuyService::payTimeout($bus_order, $order, $fail_reason);
            }
        } else {
            throw new \Exception('订单类型异常');
        }
    }

    /**
     * 验证支付信息
     */
    private function validatePayment(PayOrder $payOrder, array $payInfo)
    {
        // 验证金额
        $payTotal   = $payInfo['payTotal'];
        $orderTotal = $payOrder->getPayAmount();
        if (bccomp($payTotal, $orderTotal, 6) < 0) {
            throw new PayFailException('支付金额不匹配');
        }

        // 验证对应的合约地址
        $expectedContract = $payOrder->token_addr;
        if (strtolower($payInfo['contractAddress']) !== strtolower($expectedContract)) {
            throw new PayFailException('合约地址验证失败');
        }

        // 验证付款地址
        $expectedPayAddress = $payOrder->pay_addr;
        if (strtolower($payInfo['fromAddress']) !== strtolower($expectedPayAddress)) {
            throw new PayFailException('付款地址验证失败');
        }
    }

    private function getAsset(string $asset_code)
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException('区块链id未配置');
        }
        $chain = Chain::getInfoByChainId($chain_id);
        if (! $chain) {
            throw new LogicException('区块链信息未配置');
        }
        $asset = Asset::getPayInfo($asset_code, $chain_id);
        if (! $asset) {
            throw new LogicException("资产($asset_code)未配置");
        }
        $pay_contract = config('site.pay_contract');
        if (! $pay_contract) {
            throw new LogicException('支付合约地址未配置');
        }

        return [$asset, $chain, $pay_contract];
    }

    private function getPayService(string $asset_code)
    {
        if (! isset($this->payServices[$asset_code])) {
            if (isset(Asset::CODE_MAP[$asset_code])) {
                [$asset, $chain, $pay_contract] = $this->getAsset($asset_code);
            } else {
                throw new LogicException('资产未配置');
            }
            $this->payServices[$asset_code] = new PaymentContractService([
                'rpc'     => $chain->rpc_url,
                'address' => $pay_contract,
            ]);
        }

        return $this->payServices[$asset_code];
    }
}
