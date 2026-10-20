<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Chain;
use Illuminate\Console\Command;
use Web3\Contract;
use Web3\Web3;

class GetTokensPriceCmd extends Command
{
    protected $signature = 'app:get-token-price {chain_id?}';

    protected $description = '获取代币价格';

    // ABI for getAmountsOut function only
    private $abi = [
        [
            'inputs' => [
                ['internalType' => 'uint256', 'name' => 'amountIn', 'type' => 'uint256'],
                ['internalType' => 'address[]', 'name' => 'path', 'type' => 'address[]'],
            ],
            'name'    => 'getAmountsOut',
            'outputs' => [
                ['internalType' => 'uint256[]', 'name' => 'amounts', 'type' => 'uint256[]'],
            ],
            'stateMutability' => 'view',
            'type'            => 'function',
        ],
    ];

    private $config = [
        '56' => [
            // 'rpcUrl' => 'https://bsc-dataseed.binance.org',
            'router'   => '0x10ED43C718714eb63d5aA57B78B54704E256024E',
            'tokenOut' => '0x55d398326f99059fF775485246999027B3197955', // USDT
        ],
        '97' => [
            // 'rpcUrl' => 'https://bsc-testnet-rpc.publicnode.com',
            'router'   => '0xD99D1c33F9fC3444f8101754aBC46c52416550D1',
            'tokenOut' => '0x821F1b5d8f12550822Cc6132294599C6fC92b3d5', // tUSDT
        ],
    ];

    public function handle()
    {
        $chainId = $this->argument('chain_id') ?: config('site.asset.chain_id');
        if (! $chainId) {
            $this->error('Chain id not provided.');

            return;
        }
        if (! isset($this->config[$chainId])) {
            $this->error('Invalid chain id provided.');

            return;
        }

        $chain = Chain::where('chain_id', $chainId)->first();
        if (! $chain) {
            $this->error('Chain not found.');

            return;
        }
        $rpcUrl = $chain->rpc_url;
        if (empty($rpcUrl)) {
            $this->error('RPC URL not found.');

            return;
        }

        $asset = Asset::where('chain_id', $chain->id)->where('code', Asset::CODE_NOX)->first();
        if (! $asset) {
            $this->error('Asset not found.');

            return;
        }

        $router   = $this->config[$chainId]['router'];
        $tokenIn  = $asset->contract_address;
        $tokenOut = $this->config[$chainId]['tokenOut'];

        // 判断是否是主币
        if ($asset->type == Asset::TYPE_MAIN) {
            $this->info("Token {$asset->code} is main token");

            return;
        }

        // 判断代币合约是否存在
        if (! $tokenIn) {
            $this->error("Token {$asset->code} contract address not found");

            return;
        }

        $outputAmount = null;
        try {
            $web3     = new Web3($rpcUrl, 10);
            $contract = new Contract($web3->getProvider(), $this->abi);

            $path        = [$tokenIn, $tokenOut];
            $amountInWei = pow(10, $asset->decimals);
            $contract->at($router)->call('getAmountsOut', $amountInWei, $path, function ($err, $result) use (&$outputAmount) {
                if ($err !== null) {
                    throw new \Exception('Call getAmountsOut error: '.$err->getMessage());
                }

                if (isset($result['amounts']) && count($result['amounts']) > 1) {
                    $outputAmount = $result['amounts'][1]->toString(); // Second token amount
                }
            });
        } catch (\Exception $e) {
            $this->error('Get token price error: '.$e->getMessage());

            return;
        }
        if ($outputAmount === null) {
            $this->error('Unable to get output amount');

            return;
        }
        $outputAmount = bcdiv($outputAmount, 1000_000_000_000_000_000, 18);

        if ($outputAmount !== null) {
            $outputAmount = bcadd($outputAmount, 0, 6); // 保留6位小数
            if (bccomp($outputAmount, $asset->exchange_rate, 6) != 0) {
                $this->info("Token {$asset->code} price {$outputAmount} USDT");
                if ($outputAmount > 0) {
                    $asset->exchange_rate = $outputAmount;
                    $asset->save();
                }
            }
        } else {
            $this->error("Token {$asset->code} price empty");
        }
    }
}
