<?php

namespace App\Services\Web3;

use phpseclib\Math\BigInteger;
use Web3\Contract;
use Web3\Providers\HttpProvider;

/**
 * 提现智能合约
 */
class WithdrawContractService
{
    private $rpc;

    private $abi;

    private $address;

    private $contract;

    public function __construct(array $config, int $timeout = 10)
    {
        $this->rpc      = $config['rpc'];
        $this->abi      = $config['abi'];
        $this->address  = $config['address'];
        $this->contract = new Contract(new HttpProvider($this->rpc, $timeout), $this->abi);
    }

    // call getCurrentTimestamp
    public function getCurrentTimestamp(): ?string
    {
        $res = null;
        $this->contract->at($this->address)->call('getCurrentTimestamp', function ($err, $result) use (&$res) {
            if ($err !== null) {
                throw new \Exception('Call getCurrentTimestamp error: '.$err->getMessage());
            }
            if ($result[0] instanceof BigInteger) {
                $res = $result[0]->toString();
            } else {
                $res = $result[0];
            }
        });

        return $res;
    }

    /**
     * 检查签名是否使用
     * $sign 提现签名
     */
    public function usedSignature(string $sign)
    {
        $res = null;
        $this->contract->at($this->address)->call('usedSignature', $sign, function ($err, $result) use (&$res) {
            if ($err !== null) {
                throw new \Exception('Call usedSignature error: '.$err->getMessage());
            }
            $res = $result[0];
        });

        return $res;
    }
}
