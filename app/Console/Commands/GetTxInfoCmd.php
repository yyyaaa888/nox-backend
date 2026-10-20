<?php

namespace App\Console\Commands;

use App\Exceptions\LogicException;
use App\Models\Chain;
use App\Services\Web3\EthService;
use Illuminate\Console\Command;

class GetTxInfoCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-tx-info {hash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取交易详情';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hash  = $this->argument('hash');
        $chain = $this->getChain();

        try {
            $eth = new EthService([
                'rpc' => $chain->rpc_url,
            ]);
            $transaction = $eth->getTransactionInfo($hash);
        } catch (\Exception $e) {
            $this->error('Get transaction info error: '.$e->getMessage());

            return;
        }

        if (! $transaction) {
            $this->error('Transaction not found');

            return;
        }

        if ($transaction->status != '0x1') {
            $this->error('Transaction not successful');
            dd($transaction);

            return;
        }

        $this->info('Transaction successful');
        dd($transaction);
    }

    private function getChain()
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException('区块链id未配置');
        }
        $chain = Chain::getInfoByChainId($chain_id);
        if (! $chain) {
            throw new LogicException('区块链信息未配置');
        }

        return $chain;
    }
}
