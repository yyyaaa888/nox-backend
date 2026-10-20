<?php

namespace App\Services\Web3;

use Web3\Contract;
use Web3\Providers\HttpProvider;

/**
 * 智能合约
 */
class ContractService
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
        $this->contract->at($this->address);
    }

    // call function
    public function call(string $function, array $params = []): ?string
    {
        array_unshift($params, $function);
        array_push($params, function ($err, $result) use ($function, &$res) {
            if ($err !== null) {
                throw new \Exception('Call contract function '.$function.' error: '.$err->getMessage());
            }
            $res = $result;
        });
        $res = null;
        call_user_func_array([$this->contract, 'call'], $params);

        // $this->contract->at($this->address)->call($function, $params, function ($err, $result) use ($function, &$res) {
        //     if ($err !== null) {
        //         throw new \Exception('Call contract function ' . $function . ' error: ' . $err->getMessage());
        //     }
        //     $res = $result;
        // });
        return $res;
    }
}
