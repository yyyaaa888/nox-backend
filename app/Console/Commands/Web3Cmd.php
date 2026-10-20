<?php

namespace App\Console\Commands;

use App\Exceptions\LogicException;
use App\Models\Asset;
use App\Models\Chain;
use App\Services\Web3NewService;
use Illuminate\Console\Command;

class Web3Cmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:web3 {hash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hash = $this->argument('hash');

        [$asset, $chain] = $this->getUsdtAsset();

        $this->output->info('RPC: '.$chain->rpc_url);
        $this->output->info('资产: '.$asset->name);

        $web3            = new Web3NewService($chain, $asset);
        $transactionInfo = $web3->verifyTransactionHash2($hash);
        if (empty($transactionInfo)) {
            $this->output->error('验证无效');
        } else {
            $this->output->info('交易信息: '.json_encode($transactionInfo, JSON_PRETTY_PRINT));
        }
    }

    private function getUsdtAsset()
    {
        $chain_id = config('site.asset.chain_id');
        if (! $chain_id) {
            throw new LogicException('区块链id未配置');
        }
        $chainConfig = Chain::getInfoByChainId($chain_id);
        if (! $chainConfig) {
            throw new LogicException('区块链信息未配置');
        }
        $asset = Asset::getPayInfo(Asset::CODE_USDT, $chain_id);
        if (! $asset) {
            throw new LogicException('资产(USDT)未配置');
        }

        return [$asset, $chainConfig];
    }
}
