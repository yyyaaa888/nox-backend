<?php

namespace App\Services\Web3;

use Exception;
use Illuminate\Support\Facades\Log;
use Web3\Eth;
use Web3\Providers\HttpProvider;

class EthService
{
    private string $rpc;

    private Eth $eth;

    public function __construct(array $config, int $timeout = 10)
    {
        $this->rpc = $config['rpc'];
        $this->eth = new Eth(new HttpProvider($this->rpc, $timeout));
    }

    public function getTransactionInfo(string $hash)
    {
        try {
            $transaction = null;
            $this->eth->getTransactionReceipt($hash, function ($err, $receipt) use (&$transaction) {
                if ($err !== null) {
                    throw new \Exception('Error getting transaction receipt: '.$err->getMessage());
                }
                $transaction = $receipt;
            });

            return $transaction;
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'hash'  => $hash,
                    'error' => 'EthService getTransactionInfo error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return null;
        }
    }
}
