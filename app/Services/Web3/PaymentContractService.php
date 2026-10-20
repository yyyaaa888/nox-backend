<?php

namespace App\Services\Web3;

use Illuminate\Support\Facades\Log;
use Web3\Providers\HttpProvider;

/**
 * 支付合约
 */
class PaymentContractService
{
    private $rpc;

    private $address;

    private \Web3\Eth $eth;

    public function __construct(array $config, int $timeout = 10)
    {
        $this->rpc     = $config['rpc'];
        $this->address = $config['address'];
        $this->eth     = new \Web3\Eth(new HttpProvider($this->rpc, $timeout));
    }

    /**
     * 获取订单支付详情
     *
     * @return array|null
     */
    public function getPayInfo($orderNo)
    {
        try {
            Log::debug('开始getPayInfo调用', [
                'order_no' => $orderNo,
                'contract' => $this->address,
            ]);

            // 手动构建 getPayInfo(uint256) 调用数据
            $methodSignature = '0x4c386a26'; // getPayInfo(uint256)的keccak256哈希前4字节
            $orderNoHex      = str_pad(dechex($orderNo), 64, '0', STR_PAD_LEFT);
            $callData        = $methodSignature.$orderNoHex;

            Log::debug('构建调用数据', [
                'method_signature' => $methodSignature,
                'order_no_hex'     => $orderNoHex,
                'call_data'        => $callData,
            ]);

            // 使用已有的callContract方法进行调用
            $result = $this->callContract($callData);

            Log::debug('合约调用结果', [
                'result'        => $result,
                'result_length' => $result ? strlen($result) : 0,
            ]);

            if (! $result || $result === '0x') {
                Log::warning('合约返回空数据或0x');

                return null;
            }

            // 手动解析返回数据 (7个字段的结构体)
            $parsed = $this->parsePayInfoResult($result);

            Log::debug('解析后的数据', [
                'parsed' => $parsed,
            ]);

            return $parsed;
        } catch (\Exception $e) {
            Log::error('getPayInfo调用失败', [
                'order_no' => $orderNo,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * 调用合约方法
     */
    public function callContract(string $callData): ?string
    {
        $result = null;
        $this->eth->call([
            'to'   => $this->address,
            'data' => $callData,
        ], 'latest', function ($err, $res) use (&$result) {
            if ($err !== null) {
                throw new \Exception('Error call contract: '.$err->getMessage());
            }
            $result = $res;
        });

        return $result;
    }

    /**
     * 解析 getPayInfo 返回的原始数据
     */
    private function parsePayInfoResult(string $data): array
    {
        // 移除0x前缀
        $data = substr($data, 2);

        // 每个字段占64个十六进制字符(32字节)
        $orderId         = bchexdec(substr($data, 0, 64));
        $contractAddress = '0x'.substr($data, 88, 40); // 跳过padding
        $fromAddress     = '0x'.substr($data, 152, 40);
        $toAddress       = '0x'.substr($data, 216, 40);
        $payTotal        = bchexdec(substr($data, 256, 64));
        $payTime         = hexdec(substr($data, 320, 64));
        $isPaid          = hexdec(substr($data, 384, 64)) === 1;

        return [
            'orderId'         => $orderId,
            'contractAddress' => $contractAddress,
            'fromAddress'     => $fromAddress,
            'toAddress'       => $toAddress,
            'payTotal'        => $payTotal,
            'payTime'         => $payTime,
            'isPaid'          => $isPaid,
        ];
    }
}
