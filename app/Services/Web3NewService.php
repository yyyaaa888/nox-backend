<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Chain;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use kornrunner\Keccak;
use phpseclib\Math\BigInteger;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;

/*
 * 要计算交易的总费用（tx cost），你需要将 gas limit 和 gas price 相乘，然后加上交易的 value。以下是计算步骤：
 * Gas Limit: 这是交易所需的最大 gas 数量。
 * Gas Price: 这是你愿意为每个 gas 单位支付的价格。
 * Value: 这是交易中转移的以太币数量。
 * 公式如下：
 * Total Cost = (Gas Limit × Gas Price) + Value
 *
 * 假设：
 * Gas Limit = 215720
 * Gas Price = 400000 Gwei (1 Gwei = 10^9 wei)
 * Value = 0 wei (假设没有转移额外的以太币)
 *
 * 首先，将 Gas Price 转换为 wei：
 * Gas Price in wei = 400000 × 10^9 = 400000000000 wei
 *
 * 然后，计算 Gas Limit 和 Gas Price 的乘积：
 * Gas Cost = 215720 × 400000000000 = 86288000000000000 wei
 *
 * 如果有额外的 Value，则加上 Value：
 * Total Cost = Gas Cost + Value
 *
 *
 * 在以太坊中，gas limit 是你愿意为交易支付的最大 gas 数量，而 gas used 是实际执行交易所消耗的 gas 数量。
 * gas limit 和 gas used 之间的关系并不是线性的，因为 gas used 取决于交易的复杂性和所涉及的操作。
 */

class Web3NewService
{
    private $host; // rpc地址

    private $web3; // web3对象

    private $config; // 区块链配置

    private $abi; // 合约abi

    private $eth = null;

    private $contract;

    private $proxyUrl = '';

    private $contractAddress;

    private $chainId; // 添加 chainId 属性

    protected $contractOwnerAddressPrivateKey;

    protected $contractOwnerAddress;

    protected $decimals;

    protected $gasLimit;

    protected $gasPrice;

    public function __construct(Chain $chain, Asset $coin)
    {
        $this->setConfig($chain, $coin);
    }

    private function configureProxy(): void
    {
        if ($this->proxyUrl) {
            putenv("SOCKS5_PROXY=socks5://{$this->proxyUrl}");
        }
    }

    private function setConfig(Chain $chain, Asset $coin)
    {
        $this->proxyUrl        = env('SOCKET_HOST', '');
        $this->config          = $chain;
        $this->host            = $chain['rpc_url'];
        $this->abi             = $chain['abi'];
        $this->contractAddress = $chain['address'];
        $this->chainId         = $chain['chain_id']; // 在 setConfig 方法中设置 chainId
        $this->decimals        = $chain['decimals'];
        $this->gasPrice        = $chain['gas_price'];
        $this->gasLimit        = $chain['gas_limit'];
        $this->configureProxy();
        $this->web3 = new Web3(new HttpProvider($this->host, 10));
        $this->eth  = $this->web3->eth;
        // if ($coinName) {
        //     $coin = Asset::where('name', $coinName)->first();
        //     $this->contractOwnerAddressPrivateKey = isset($coin->withdraw_key) ? Crypt::decryptString($coin->withdraw_key) : null;
        //     $this->contractOwnerAddress = $coin->withdraw_address;
        //     $this->abi = $coin->contract_abi;
        //     $this->contractAddress = $coin->contract_address;
        // }
        $this->contractOwnerAddressPrivateKey = $coin->withdraw_key;
        $this->contractOwnerAddress           = $coin->withdraw_address;
        $this->abi                            = $coin->contract_abi;
        $this->contractAddress                = $coin->contract_address;
    }

    public function getPayInfo(string $orderId)
    {
        try {
            $info = Chain::info();
            // Create a contract instance using the Web3 provider and ABI
            $contract = new Contract($this->web3->provider, $info->collect_abi);

            $transaction = null;
            // Get the transaction receipt using the transaction hash
            $contract->at($info->collect_contract)->call('payInfos', $orderId, function ($err, $receipt) use (&$transaction) {
                if ($err !== null) {
                    throw new \Exception('Error getting transaction receipt: '.$err->getMessage());
                }
                if (! empty($receipt['orderId']) && ! empty($receipt['contractAddress']) && ! empty($receipt['fromAddress']) && ! empty($receipt['payTotal'])) {
                    $transaction['order_id']         = (string) $receipt['orderId']->value;
                    $transaction['contract_address'] = $receipt['contractAddress'];
                    $transaction['from_address']     = $receipt['fromAddress'];
                    $transaction['pay_total']        = (string) $receipt['payTotal']->value;
                } else {
                    throw new \Exception('payInfo参数错误');
                }
            });

            return $transaction;
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'Web3Service getTransactionInfo error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return null;
        }
    }

    /**
     * Get transaction information based on transaction hash.
     *
     * 检查sendRawTransaction后的交易状态
     *
     * @param  string  $txHash  The transaction hash to retrieve information for.
     */
    public function getTransactionInfo(string $txHash)
    {
        try {
            // Create a contract instance using the Web3 provider and ABI
            $contract = new Contract($this->web3->provider, $this->abi);

            $transaction = null;
            // Get the transaction receipt using the transaction hash
            $contract->eth->getTransactionReceipt($txHash, function ($err, $receipt) use (&$transaction) {
                if ($err !== null) {
                    throw new \Exception('Error getting transaction receipt: '.$err->getMessage());
                }

                // Check if the transaction exists and has a successful status
                if ($receipt && $receipt->status === '0x1') {
                    $transaction = $receipt; // Return the transaction information
                }
            });

            return $transaction;
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'Web3Service getTransactionInfo error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return null;
        }
    }

    /**
     * 获取指定账户的余额
     *
     * @param  string  $account  以太坊账户地址
     * @return string 返回账户余额
     *
     * @throws \Exception 如果获取余额时发生错误
     */
    public function getBalance($account)
    {
        $balance  = '';
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->getBalance($account, function ($err, $amount) use (&$balance) {
            if ($err !== null) {
                throw new \Exception('Error getting balance: '.$err->getMessage());
            }
            $balance = $amount;
        });

        return $balance;
    }

    public function getTokenBalance($account)
    {
        $balance  = '';
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->at($this->contractAddress)->call('balanceOf', $account, function ($err, $result) use (&$balance) {
            if ($err !== null) {
                throw new \Exception('Error getting token balance: '.$err->getMessage());
            }
            $balance = $result[0]->toString();
        });

        // 将余额转换为小数形式
        $balance = bcdiv($balance, bcpow('10', $this->decimals, 0), $this->decimals);
        // 保留小数点后8位
        $balance = bcdiv($balance, '1', 8);

        return $balance;
    }

    public function getEtherBalance($hexBalance)
    {
        // 将十六进制字符串转换为十进制数字
        $balance = hexdec($hexBalance);

        // 将十进制数字转换为以太币金额
        $etherBalance = bcdiv($balance, bcpow('10', '18', 0), 18);

        return $etherBalance;
    }

    public function convertEthToUsdt($ethBalance, $ethToUsdtRate)
    {
        // 计算 USDT 余额
        $usdtBalance = bcmul($ethBalance, $ethToUsdtRate, 2);

        return $usdtBalance;
    }

    /**
     * 获取指定地址的交易计数（nonce）
     *
     * @param  string  $address  以太坊地址
     * @return string 返回十进制的 nonce 值
     *
     * @throws \Exception 如果获取交易计数时发生错误
     *
     *  $nonce 是一个用于标识以太坊账户交易顺序的整数。每个以太坊账户都有一个唯一的 nonce 值，它表示该账户已经发送的交易数量。nonce 的作用如下：
     *  1.防止重放攻击：每个交易都有一个唯一的 nonce 值，确保相同的交易不能被重复提交。
     *  2.交易顺序：nonce 确保交易按顺序执行。较低的 nonce 交易必须先被确认，然后才能确认较高的 nonce 交易。
     *  3.交易替换：如果你想替换一个未确认的交易，你需要使用相同的 nonce 值，并且新的交易的 gas price 必须高于原交易的 gas price。
     */
    private function getTransactionCount($address)
    {
        $count    = '';
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->getTransactionCount($address, function ($err, $result) use (&$count) {
            if ($err !== null) {
                throw new \Exception('Error getting transaction count: '.$err->getMessage());
            }
            if ($result instanceof BigInteger) {
                $count = $result->toString();
            } else {
                $count = $result;
            }
        });

        return $count; // 确保返回的是十进制的 nonce 值
    }

    public function gasPrice2()
    {
        $price = null;
        $this->eth->gasPrice(function ($err, $gasPrice) use (&$price) {
            if ($err !== null) {
                throw new \Exception('Error getting gas price: '.$err->getMessage());
            }
            if ($gasPrice instanceof BigInteger) {
                $price = $gasPrice->toString();
            } else {
                $price = $gasPrice;
            }
        });
        if ($price === null) {
            throw new \Exception('Unable to get gas price');
        }

        return $price;
    }

    public function estimateGas2($txParams)
    {
        $gasLimit = null;
        $this->eth->estimateGas($txParams, function ($err, $gas) use (&$gasLimit) {
            if ($err !== null) {
                throw new \Exception('Error getting estimating gas: '.$err->getMessage().' '.$err->getCode());
            }
            if ($gas instanceof BigInteger) {
                $gasLimit = $gas->toString();
            } else {
                $gasLimit = $gas;
            }
        });
        if ($gasLimit === null) {
            throw new \Exception('Unable to get gas limit');
        }

        return $gasLimit;
    }

    public function gasPrice()
    {
        $price    = '';
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->getGasPrice(function ($err, $gasPrice) use (&$price) {
            if ($err !== null) {
                throw new \Exception('Error getting gas price: '.$err->getMessage());
            }
            $price = $gasPrice;
        });

        return $price;
    }

    /**
     * 发送已签名的交易
     *
     * @param  string  $signedTx  已签名的交易数据
     * @return string 返回交易哈希
     *
     * @throws \Exception 如果发送交易时发生错误
     *
     * "Error sending transaction: replacement transaction underpriced" 意味着你尝试发送的交易是对之前未确认交易的替换，但新交易的 gas price 低于或等于原交易的 gas price。以太坊网络要求替换交易的 gas price 必须高于原交易的 gas price，通常高出至少 10-12%。
     *  解决方法：
     *  1. 提高新交易的 gas price，确保它比原交易的 gas price 高出至少 10-12%。
     *
     * "Error sending transaction: already known" 意味着你尝试发送的交易已经被节点识别并记录。这通常发生在以下几种情况下：
     *  1. 重复发送：你可能多次发送了相同的交易。
     *  2.网络延迟：由于网络延迟，节点已经接收到并处理了该交易，但你的客户端还未收到确认。
     *  3.交易池：交易已经在交易池中等待被打包到区块中。
     * 解决方法：
     *  1. 确认交易是否已经成功发送并等待确认。
     *  2. 避免重复发送相同的交易。
     */
    public function sendRawTransaction($signedTx)
    {
        $hash     = '';
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->sendRawTransaction($signedTx, function ($err, $txHash) use (&$hash) {
            if ($err !== null) {
                if (strpos($err->getMessage(), 'already known') !== false) {
                    // Handle the "already known" error specifically
                    throw new \Exception('Error sending transaction: already known. The transaction has already been recognized by the node. 交易已知错误：交易已被节点识别。');
                } elseif (strpos($err->getMessage(), 'replacement transaction underpriced') !== false) {
                    // Handle the "replacement transaction underpriced" error specifically
                    throw new \Exception('Error sending transaction: replacement transaction underpriced. Increase the gas price. 交易发送错误：替换交易的 gas 价格过低。请提高 gas 价格。');
                } else {
                    throw new \Exception('Error sending transaction: '.$err->getMessage());
                }
            } else {
                $hash = $txHash;
            }
        });

        return $hash;
    }

    public function getDataForTransfer($address, $amount)
    {
        $contract = new Contract($this->web3->provider, $this->abi);

        return '0x'.$contract->at($this->contractAddress)->getData('transfer', $address, $amount);
    }

    public function accounts()
    {
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->accounts(function ($err, $accounts) {
            if ($err !== null) {
                throw new \Exception('Error getting accounts: '.$err->getMessage());
            }

            return $accounts;
        });
    }

    public function getSignedTx(array $txParams): string
    {
        $tx = new Transaction($txParams);

        return '0x'.$tx->sign($this->contractOwnerAddressPrivateKey);
    }

    public function sendTransaction($to, $value)
    {
        try {
            $value = $value * bcpow(10, $this->decimals);
            // 获取正确的 nonce 值
            $nonce = $this->getTransactionCount($this->contractOwnerAddress);

            // 设置 gas limit 和 gas price 并计算 gas fee
            $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9
            $gasLimit = 800000;
            // 构建交易参数
            $txParams = [
                'from'     => $this->contractOwnerAddress,
                'to'       => $to,
                'value'    => '0x'.dechex($value),
                'gas'      => '0x'.dechex($gasLimit), // 十六进制字符串
                'gasPrice' => '0x'.dechex($gasPrice),
                'nonce'    => '0x'.dechex($nonce),
                'chainId'  => '0x'.dechex($this->chainId),
            ];

            // 估算 gas
            $gasLimit = $this->estimateGas($txParams)->toString();

            // 计算总的 gas fee
            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

            // 检查余额是否足够
            $balance = $this->getBalance($this->contractOwnerAddress);
            if (bccomp($balance, $totalGasFee) < 0) {
                throw new \Exception('Insufficient funds for gas * price + value.');
            }

            $txParams['gas']     = '0x'.dechex($gasLimit); // 设置估算的 gas limit
            $txParams['chainId'] = $this->chainId; // 发送交易时使用整数

            // 获取签名的交易
            $signedTx = $this->getSignedTx($txParams);

            // 发送交易
            $txHash = $this->sendRawTransaction($signedTx);

            // 检查交易状态
            $receipt = $this->getTransactionInfo($txHash);
            if ($receipt === null) {
                throw new \Exception('Transaction not found in the blockchain.');
            }

            return ['success' => true, 'txHash' => $txHash];
        } catch (Exception $e) {
            // 记录错误日志或返回适当的错误信息
            // dd($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function withdraw(string $to, string $amount)
    {
        try {
            $from = $this->contractOwnerAddress;
            if (! $from) {
                throw new \Exception('From address is empty.');
            }
            if (! $to) {
                throw new \Exception('To address is empty.');
            }

            $amount = bcmul($amount, pow(10, $this->decimals));

            $nonce    = $this->getTransactionCount($from);
            $data     = $this->getDataForTransfer($to, $amount);
            $txParams = [
                'nonce'   => '0x'.dechex($nonce),
                'from'    => $from,
                'to'      => $this->contractAddress,
                'value'   => '0x0',
                'chainId' => '0x'.dechex($this->chainId),
                'data'    => $data,
            ];

            $gasPrice = $this->gasPrice2();
            $gasLimit = $this->estimateGas2($txParams);

            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);
            $balance     = $this->getBalance($from);
            if (bccomp($balance, $totalGasFee) < 0) {
                throw new \Exception('Insufficient funds for gas * price + value.');
            }

            $txParams['gas']      = '0x'.dechex($gasLimit);
            $txParams['gasPrice'] = '0x'.dechex($gasPrice);
            $txParams['chainId']  = $this->chainId;
            // $txParams['data']     = $data;

            $signedTx = $this->getSignedTx($txParams);
            $txHash   = $this->sendRawTransaction($signedTx);

            return $this->checkTx($txHash);
        } catch (Exception $e) {
            // 记录错误日志或返回适当的错误信息
            Log::error(
                json_encode([
                    'data' => [
                        'to'     => $to,
                        'amount' => $amount,
                    ],
                    'error' => 'Withdraw error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'message' => 'Withdraw failed: please contact support'];
        }
    }

    public function withdraw2(string $to, string $amount)
    {
        try {
            $from = $this->contractOwnerAddress;
            if (! $from) {
                throw new \Exception('From address is empty.');
            }
            if (! $to) {
                throw new \Exception('To address is empty.');
            }

            $amount = bcmul($amount, pow(10, $this->decimals));

            $nonce    = $this->getTransactionCount($from);
            $data     = $this->getDataForTransfer($to, $amount);
            $txParams = [
                'from'  => $from, // 出金地址
                'to'    => $this->contractAddress, // 代币合约
                'value' => '0x0',
                'nonce' => '0x'.dechex($nonce),
                // 'chainId' => '0x' . dechex($this->chainId),
                'data' => $data,
            ];

            $gasPrice = $this->gasPrice2();
            $gasLimit = $this->estimateGas2($txParams);

            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);
            $balance     = $this->getBalance($from);
            if (bccomp($balance, $totalGasFee) < 0) {
                throw new \Exception('Insufficient funds for gas * price + value.');
            }

            $txParams['gas']      = '0x'.dechex($gasLimit);
            $txParams['gasPrice'] = '0x'.dechex($gasPrice);
            $txParams['chainId']  = $this->chainId;
            // $txParams['data']     = $data;

            $signedTx = $this->getSignedTx($txParams);
            $txHash   = $this->sendRawTransaction($signedTx);

            return ['success' => true, 'hash' => $txHash];
        } catch (Exception $e) {
            // 记录错误日志或返回适当的错误信息
            Log::error(
                json_encode([
                    'data' => [
                        'to'     => $to,
                        'amount' => $amount,
                    ],
                    'error' => 'Withdraw2 error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'hash' => null, 'message' => 'Withdraw failed: please contact support'];
        }
    }

    // 检查交易状态
    public function checkTx(string $hash, int $timeout = 6)
    {
        try {
            $time = time();
            while (true) {
                $start   = time();
                $receipt = $this->getTransactionInfo($hash);
                if ($receipt === null) {
                    if (time() - $time >= $timeout) {
                        throw new \Exception('Transaction not found in the blockchain.');
                    }
                    if (time() - $start < 1) {
                        sleep(1);
                    }
                } else {
                    break;
                }
            }

            if (! is_array($receipt)) {
                if (property_exists($receipt, 'status') && $receipt->status !== '0x1') {
                    throw new \Exception('Transaction failed.');
                }
            } else {
                if (isset($receipt['status']) && $receipt['status'] !== '0x1') {
                    throw new \Exception('Transaction failed.');
                }
            }

            return ['success' => true, 'hash' => $hash];
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'data' => [
                        'hash' => $hash,
                    ],
                    'error' => 'checkTx error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'hash' => $hash, 'message' => 'checkTx failed: please contact support'];
        }
    }

    /**
     * Mint new tokens to a specified address.
     *
     * @param  string  $address  The address to mint tokens to.
     * @param  int  $amount  The amount of tokens to mint.
     * @return string The transaction hash of the mint transaction.
     *
     * @throws \Exception If there are insufficient funds for gas or if the transaction fails.
     */
    public function mint($address, $amount)
    {
        $amount = bcmul($amount, pow(10, $this->decimals));

        $contract = new Contract($this->web3->provider, $this->abi);
        $data     = $contract->at($this->contractAddress)->getData('mint', $address, $amount);

        // 获取正确的 nonce 值
        $nonce = $this->getTransactionCount($this->contractOwnerAddress); // 使用账户地址

        // 设置 gas limit 和 gas price 并计算 gas fee
        $gasLimit = 800000;
        $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9

        $txParams = [
            'from'     => $this->contractOwnerAddress,
            'to'       => $this->contractAddress,
            'data'     => '0x'.$data, // 十六进制字符串
            'gas'      => '0x'.dechex($gasLimit), // 十六进制字符串
            'gasPrice' => '0x'.dechex($gasPrice), // 十六进制字符串
            'nonce'    => '0x'.dechex($nonce), // 十六进制字符串 在 mint 方法中，getTransactionCount 方法的 address 参数应该传递发送交易的账户地址，即你用来签名和发送交易的账户地址。
            'chainId'  => '0x'.dechex($this->chainId),
            'value'    => '0x0',
        ];

        // 估算 gas
        $gasLimit = $this->estimateGas($txParams)->toString();

        // 计算总的 gas fee
        $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

        // 检查余额是否足够
        $balance = $this->getBalance($this->contractOwnerAddress);
        if (bccomp($balance, $totalGasFee) < 0) {
            throw new \Exception('Insufficient funds for gas * price + value.');
        }

        $txParams['gas']     = '0x'.dechex($gasLimit); // 设置估算的 gas limit
        $txParams['chainId'] = $this->chainId; // 发送交易时使用整数

        $signedTx = $this->getSignedTx($txParams);
        $txHash   = $this->sendRawTransaction($signedTx);

        // 检查交易状态
        $receipt = $this->getTransactionInfo($txHash);
        if ($receipt === null) {
            throw new \Exception('Transaction not found in the blockchain.');
        }

        // 检查交易是否成功
        if (isset($receipt['status']) && $receipt['status'] === '0x1') {
            throw new \Exception('Transaction failed.');
        }

        return $txHash;
    }

    /**
     * 获取最新区块的 gas limit
     *
     * @return string|null 返回最新区块的 gas limit，如果获取失败则返回 null
     *
     * @throws \Exception 如果获取区块信息时发生错误
     *
     *  获取区块的gasLimit
     *  十六进制
     *  在以太坊中，gas limit 是一个无单位的整数，表示一个区块或交易可以消耗的最大 gas 数量。它不是以太币或其他货币单位，而是一个纯粹的计量单位，用于衡量计算和存储操作的成本。
     *  解释 gas limit
     *  区块 gas limit：表示一个区块中所有交易可以消耗的最大 gas 数量。
     *  交易 gas limit：表示单个交易可以消耗的最大 gas 数量。
     *  示例
     *  假设 getBlockGasLimit 方法返回 70000000，这意味着当前区块的 gas limit 是 70,000,000 gas。这个值表示整个区块中所有交易的总 gas 消耗不能超过 70,000,000 gas。
     */
    public function getBlockGasLimit()
    {
        $blockGasLimit = null;
        $contract      = new Contract($this->web3->provider, $this->abi);
        $contract->eth->getBlockByNumber('latest', false, function ($err, $block) use (&$blockGasLimit) {
            if ($err !== null) {
                throw new \Exception('Error getting block: '.$err->getMessage());
            }
            $blockGasLimit = $block->gasLimit;
        });

        return $blockGasLimit;
    }

    /**
     * 销毁指定数量的代币
     *
     * @param  int  $amount  要销毁的代币数量
     * @return string 交易哈希
     *
     * @throws \Exception 如果交易未找到或余额不足
     */
    public function burn($amount)
    {
        // 将代币数量转换为最小单位
        // $amount = $amount * bcpow(10, $this->decimals);
        $amount   = bcmul($amount, pow(10, $this->decimals));
        $contract = new Contract($this->web3->provider, $this->abi);
        $data     = $contract->at($this->contractAddress)->getData('burn', $amount);

        // 获取正确的 nonce 值
        $nonce = $this->getTransactionCount($this->contractOwnerAddress); // 使用账户地址

        // 设置 gas limit 和 gas price 并计算 gas fee
        $gasLimit = 800000;
        $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9

        // 构建交易参数
        $txParams = [
            'from'     => $this->contractOwnerAddress,
            'to'       => $this->contractAddress,
            'data'     => '0x'.$data, // 十六进制字符串
            'gas'      => '0x'.dechex($gasLimit), // 十六进制字符串
            'gasPrice' => '0x'.dechex($gasPrice), // 十六进制字符串
            'nonce'    => '0x'.dechex($nonce), // 十六进制字符串 在 mint 方法中，getTransactionCount 方法的 address 参数应该传递发送交易的账户地址，即你用来签名和发送交易的账户地址。
            'chainId'  => '0x'.dechex($this->chainId),
            'value'    => '0x0',
        ];

        // 估算 gas
        $gasLimit = $this->estimateGas($txParams)->toString();

        // 计算总的 gas fee
        $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

        // 检查余额是否足够
        $balance = $this->getBalance($this->contractOwnerAddress);
        if (bccomp($balance, $totalGasFee) < 0) {
            throw new \Exception('Insufficient funds for gas * price + value.');
        }

        // 设置估算的 gas limit
        $txParams['gas']     = '0x'.dechex($gasLimit);
        $txParams['chainId'] = $this->chainId; // 发送交易时使用整数

        // 获取签名的交易
        $signedTx = $this->getSignedTx($txParams);
        // 发送交易
        $txHash = $this->sendRawTransaction($signedTx);

        // 检查交易状态
        $receipt = $this->getTransactionInfo($txHash);
        if ($receipt === null) {
            throw new \Exception('Transaction not found in the blockchain.');
        }

        return $txHash;
    }

    /*
     * 估算执行特定交易所需的 gas 数量
     */
    public function estimateGas($txParams)
    {
        $gasLimit = null;
        $contract = new Contract($this->web3->provider, $this->abi);
        $contract->eth->estimateGas($txParams, function ($err, $gas) use (&$gasLimit) {
            if ($err !== null) {
                throw new \Exception('Error estimating gas: '.$err->getMessage().' '.$err->getCode());
            }
            $gasLimit = $gas;
        });

        if ($gasLimit === null) {
            throw new \Exception('Unable to estimate gas.');
        }

        return $gasLimit;
    }

    /*
     * 安全转账方法
     *
     * @param string $from 发送方地址
     * @param string $to 接收方地址
     * @param string $tokenId 代币ID
     * @param string $data 附加数据
     * @return string 交易哈希
     * @throws \Exception
     */
    public function safeTransferFrom($to, $data = '') // $tokenId = $this->contractAddress
    {
        // 获取正确的 nonce 值
        $nonce = $this->getTransactionCount($this->contractOwnerAddress); // 使用账户地址
        $from  = $this->contractOwnerAddress;

        $contract = new Contract($this->web3->provider, $this->abi);
        $data     = $contract->at($this->contractAddress)->getData('safeTransferFrom', $from, $to, $this->contractAddress, $data);
        $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9

        // 构建交易参数
        $txParams = [
            'from'     => $from,
            'to'       => $this->contractAddress,
            'data'     => '0x'.$data, // 十六进制字符串
            'gas'      => '0x0', // 先设置为0，稍后估算
            'gasPrice' => '0x'.dechex($gasPrice), // 十六进制字符串
            'chainId'  => '0x'.dechex($this->chainId),
            'value'    => '0x0',
            'nonce'    => '0x'.dechex($nonce),
        ];

        // 估算 gas
        $gasLimit = $this->estimateGas($txParams)->toString();

        // 计算总的 gas fee
        $totalGasFee = bcmul($gasLimit, $txParams['gasPrice'], 0);

        // 检查余额是否足够
        $balance = $this->getBalance($from);
        if (bccomp($balance, $totalGasFee) < 0) {
            throw new \Exception('Insufficient funds for gas * price + value.');
        }

        $txParams['gas'] = '0x'.dechex($gasLimit); // 设置估算的 gas limit

        // 签名交易
        $signedTx = $this->getSignedTx($txParams);
        $txHash   = $this->sendRawTransaction($signedTx);

        // 检查交易状态
        $receipt = $this->getTransactionInfo($txHash);
        if ($receipt === null) {
            throw new \Exception('Transaction not found in the blockchain.');
        }

        return $txHash;
    }

    /**
     * 验证交易哈希
     *
     * @param  string  $txHash
     * @return object|false
     *
     * @throws \Exception
     */
    public function verifyTransactionHash($txHash)
    {
        // 获取交易信息
        $transactionInfo = $this->getTransactionInfo($txHash);
        $data            = new \stdClass();
        if ($transactionInfo && $transactionInfo->logs) {
            $data->amount         = hexdec($transactionInfo->logs[0]->data);
            $data->from           = $transactionInfo->from;
            $data->to             = $transactionInfo->to;
            $data->to_log_address = '0x'.substr($transactionInfo->logs[0]->topics[2], -40);

            return $data;
        }

        return false;
    }

    /**
     * 验证交易哈希2
     *
     * @param  string  $txHash
     * @return object|false
     *
     * @throws \Exception
     */
    public function verifyTransactionHash2($txHash)
    {
        // 获取交易信息
        $transactionInfo = $this->getTransactionInfo($txHash);
        $data            = new \stdClass();
        if ($transactionInfo && $transactionInfo->logs) {
            $data->amount         = bchexdec($transactionInfo->logs[0]->data);
            $data->from           = $transactionInfo->from;
            $data->to             = $transactionInfo->to;
            $data->to_log_address = '0x'.substr($transactionInfo->logs[0]->topics[2], -40);

            return $data;
        }

        return false;
    }

    /**
     * 验证投资USDD交易哈希
     *
     * @param  string  $txHash
     * @return object|false
     *
     * @throws \Exception
     */
    public function verifyUSDDTransactionHash($txHash)
    {
        // 获取交易信息
        $transactionInfo = $this->getTransactionInfo($txHash);
        $data            = new \stdClass();
        if ($transactionInfo) {
            $data->amount         = hexdec($transactionInfo->logs[0]->data);
            $data->from           = $transactionInfo->from;
            $data->to             = $transactionInfo->to;
            $data->to_log_address = '0x'.substr($transactionInfo->logs[0]->topics[2], -40);

            return $data;
        }

        return false;
    }

    public static function getPairs($infuraUrl, $contractAddress, $contractAbi, $pairType)
    {
        //        return 1;
        //        putenv('HTTP_PROXY=127.0.0.1:7890'); // 使用本地代理
        //        putenv('HTTPS_PROXY=127.0.0.1:7890'); // 使用本地代理
        $cachedData = Redis::get('getReservePairs_data'.$pairType);
        if ($cachedData) {
            return $cachedData;
        }

        $ratioInfo = 0;

        $web3     = new Web3($infuraUrl);
        $contract = new Contract($web3->provider, $contractAbi);

        try {
            $contract->at($contractAddress)->call('getReserves', function ($err, $reserves) use (&$ratioInfo) {
                if ($err !== null) {
                    return 0;
                }
                if ($err) {
                    throw new \Exception($err);
                }

                // 获取储备量
                $reserve0 = $reserves['_reserve0'];
                $reserve1 = $reserves['_reserve1'];

                // 将 BigInteger 转换为字符串
                $reserve0Str = $reserve0->toString();
                $reserve1Str = $reserve1->toString();

                $ratio     = self::getReserveRatio($reserve0Str, 18, $reserve1Str, 18);
                $ratioInfo = number_format($ratio, 8, '.', '');
                // echo "The ratio of token0 to token1 is: " . $reserve0 ;
            });
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'getPairs error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );
        }

        Redis::setex('getReservePairs_data'.$pairType, 180, $ratioInfo);

        return (float) $ratioInfo;
    }

    public static function getReserveRatio($reserve0, $reserve0Decimals, $reserve1, $reserve1Decimals)
    {
        // 将 reserve0 转换为 18 位小数
        // $normalizedReserve0 = bcmul($reserve0, bcpow(10, 18 - $reserve0Decimals , 0));
        $normalizedReserve0 = bcmul($reserve0, bcpow(10, $reserve1Decimals - 18, 0));

        // 将 reserve1 转换为 18 位小数
        $normalizedReserve1 = bcmul($reserve1, bcpow(10, $reserve1Decimals - 18, 0));

        // 计算交易对比例
        $ratio = bcdiv($normalizedReserve0, $normalizedReserve1, 18);

        return $ratio;
    }

    /*
    * 调用合约方法
    */
    public function callContractMethod($infuraUrl, $contractAddress, $contractAbi, $methodName, $params = [])
    {
        try {
            $web3     = new Web3($infuraUrl);
            $contract = new Contract($web3->provider, $contractAbi);

            $result = null;
            $contract->at($contractAddress)->call($methodName, 1, 2, function ($err, $result) {
                if ($err) {
                    throw new \Exception($err);
                }

                return $result;
            });

            return $result;
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'Web3Service callContractMethod error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return false;
        }
    }

    /*
     * 闪兑（Flash Swap）：
     * 定义：允许用户先获得所需的代币，然后在同一交易中用其他代币支付。
     * 目的：主要用于代币交换，但可以在支付之前使用获得的代币。
     * 过程：获取目标代币 -> 执行操作（可选）-> 支付源代币（都在同一个交易中）。
     * 特点：如果在交易结束时未能支付足够的源代币，整个交易会被回滚。
     * 闪兑通常是在特定的交易对（如 Uniswap 的流动性池）中进行
     * 例如，使用 Uniswap V2 的 swap 函数进行闪兑的基本流程是：
     * 1. 调用 swap 函数，指定要获取的代币数量。
     * 2. 合约会立即发送请求的代币到指定地址。
     * 3. 在同一交易中，您需要确保合约收到足够的另一种代币作为支付。
     * 4. 如果支付不足，整个交易会被回滚。
     * 这个过程允许您在实际支付之前先使用获得的代币，这就是闪兑的核心特性。
     *
     * Flash Swap Helper 合约的职责：
     * 1. 接收从交易对合约发送的 USDD
     * 2. 执行必要的操作（例如，将 USDD 发送给最终用户）
     * 3. 确保足够的 USDT 被发送回交易对合约
     * Flash Swap Helper 合约的 Solidity 代码大致如下：
     *
     *
     pragma solidity ^0.6.6;

    import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
    import "@uniswap/v2-core/contracts/interfaces/IUniswapV2Pair.sol";
    import "@uniswap/v2-core/contracts/interfaces/IUniswapV2Callee.sol";

    contract FlashSwapHelper is IUniswapV2Callee {
    address public usdtAddress;
    address public usddAddress;

    constructor(address _usdtAddress, address _usddAddress) public {
        usdtAddress = _usdtAddress;
        usddAddress = _usddAddress;
    }

    function usdtToUsddCallback(address to) external {
        // 确保调用者是交易对合约
        require(msg.sender == IUniswapV2Pair(msg.sender).token0() ||
                msg.sender == IUniswapV2Pair(msg.sender).token1(), "Unauthorized");

        // 获取借入的 USDD 数量
        uint256 usddAmount = IERC20(usddAddress).balanceOf(address(this));

        // 将 USDD 发送给用户
        IERC20(usddAddress).transfer(to, usddAmount);

        // 计算需要支付的 USDT 数量（这里需要考虑滑点和手续费）
        uint256 usdtAmount = calculateUsdtAmount(usddAmount);

        // 确保我们有足够的 USDT 余额
        require(IERC20(usdtAddress).balanceOf(address(this)) >= usdtAmount, "Insufficient USDT");

        // 将 USDT 发送回交易对合约
        IERC20(usdtAddress).transfer(msg.sender, usdtAmount);
    }

    function calculateUsdtAmount(uint256 usddAmount) internal returns (uint256) {
        // 这里需要实现计算逻辑，可能需要调用预言机或其他价格源
        // 返回的金额应该包含滑点和手续费
    }

    // 其他必要的函数...
}
     *
     *
     */
    public function flashSwapUSDTToUSDD($toAddress, $amount)
    {
        $infuraUrl       = getGlobalConfig('infuraUrl');
        $contractAddress = getGlobalConfig('pair_USDT_USDD');
        $contractAbi     = getGlobalConfig('pair_USDT_USDD_ABI');

        $web3     = new Web3($infuraUrl);
        $contract = new Contract($web3->provider, $contractAbi, $contractAddress);

        $amount0Out = 0; // USDT输出量（假设USDT是token0）
        $amount1Out = $amount; // USDC输出量（假设USDC是token1，这里是1000 USDC）

        // 准备回调数据 使用 usdtToUsddCallback 方法作为回调函数，这个函数应该在 Flash Swap Helper 合约中定义。
        $callbackData = $contract->getData('usdtToUsddCallback', $toAddress);

        // 获取正确的 nonce 值
        $nonce = $this->getTransactionCount($this->contractOwnerAddress); // 使用账户地址

        // 准备交易数据
        $data = $contract->getData('swap', $amount0Out, $amount1Out, $toAddress, $callbackData);

        $gasLimit = 800000;
        $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9

        // 发送交易
        $txParams = [
            'to'       => $contractAddress,
            'value'    => '0x0',
            'data'     => '0x'.$data,
            'gas'      => '0x'.dechex($gasLimit),
            'gasPrice' => '0x'.dechex($gasPrice),
            'nonce'    => '0x'.dechex($nonce),
            'chainId'  => $this->chainId,
        ];

        // 签名交易
        $signedTx = $this->getSignedTx($txParams);
        $txHash   = $this->sendRawTransaction($signedTx);

        // 检查交易状态
        $receipt = $this->getTransactionInfo($txHash);
        if ($receipt === null) {
            throw new \Exception('Transaction not found in the blockchain.');
        }

        return $txHash;
    }

    public function removeLiquidity($amount)
    {
        $infuraUrl       = getGlobalConfig('infuraUrl');
        $contractAddress = getGlobalConfig('router_contract_address');
        $contractAbi     = getGlobalConfig('router_contract_abi');
        $tokenA          = getGlobalConfig('pancake_USDD_contract_address'); // USDD
        $tokenB          = getGlobalConfig('pancake_TNB_contract_address'); // TNB
        $to              = getGlobalConfig('pancake_address');
        $privateKey      = 'eyJpdiI6Ii94a2dmT3N0Z2tjeUpOQnpSb3V6TWc9PSIsInZhbHVlIjoiSTBEcFppS0hhaWxRM3NmYkNxT3BuRjhrQ1FsREF3K1FxNlY0ZDROS09tQ2o4TTNXYmhQMUZRZkhPWFBTUHlpM2JhYnYxeEEvVnhCdWZ6V3dtMmlGR0I0M0lLRkM1NlZTM0FWS01EcElLekE9IiwibWFjIjoiNGNjOWQ1ODMzNzI3MDQ3Y2U1YmE3NTAyMDc5YTNiMjczNTZiODM0NjUyYzdlZWQ1MWE1NzdmZmNkZmM1OTYzNSIsInRhZyI6IiJ9';
        $privateKey      = Crypt::decryptString($privateKey);

        $amount   = bcmul($amount, pow(10, $this->decimals));
        $web3     = new Web3($infuraUrl);
        $contract = new Contract($web3->provider, $contractAbi);

        try {
            // 获取正确的 nonce 值
            $nonce = $this->getTransactionCount($to);

            $liquidity   = $amount;
            $amountAMin  = 0;
            $amountBMin  = 0;
            $deadlineHex = time() + 300;

            // 构造交易数据
            $data = $contract->at($contractAddress)->getData('removeLiquidity', $tokenA, $tokenB, $liquidity, $amountAMin, $amountBMin, $to, $deadlineHex);

            $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9
            $gasLimit = 800000;

            // 交易参数
            $txParams = [
                'to'       => $contractAddress,
                'data'     => '0x'.$data,
                'from'     => $to,
                'value'    => '0x0',
                'gas'      => '0x'.dechex($gasLimit), // 十六进制字符串
                'gasPrice' => '0x'.dechex($gasPrice),
                'nonce'    => '0x'.dechex($nonce),
                'chainId'  => '0x'.dechex($this->chainId),
            ];

            // 估算 gas
            $gasLimit = $this->estimateGas($txParams)->toString();

            $txParams['gas']     = '0x'.dechex($gasLimit); // 设置估算的 gas limit
            $txParams['chainId'] = $this->chainId; // 发送交易时使用整数

            $tx       = new Transaction($txParams);
            $signedTx = '0x'.$tx->sign($privateKey);

            $txHash = $this->sendRawTransaction($signedTx);

            // 检查交易状态
            $receipt = $this->getTransactionInfo($txHash);
            //            if ($receipt === null) {
            //                throw new \Exception('Transaction not found in the blockchain.');
            //            }

            // 检查交易是否成功（状态为 0x1 表示成功）
            if (isset($receipt->status) && $receipt->status !== '0x1') {
                throw new \Exception('Transaction failed.');
            }
            Log::info(
                json_encode([
                    'success' => 'removeLiquidity success: amount: '.$amount.', hash: '.$txHash,
                ])
            );

            return ['success' => true, 'hash' => $txHash];
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'removeLiquidity error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // 授权账户给pair ,参数是spender amount
    public function approve($amount)
    {
        $from       = getGlobalConfig('pancake_address');
        $spender    = getGlobalConfig('router_contract_address'); // router
        $pair       = $this->contractAddress;
        $privateKey = 'eyJpdiI6Ii94a2dmT3N0Z2tjeUpOQnpSb3V6TWc9PSIsInZhbHVlIjoiSTBEcFppS0hhaWxRM3NmYkNxT3BuRjhrQ1FsREF3K1FxNlY0ZDROS09tQ2o4TTNXYmhQMUZRZkhPWFBTUHlpM2JhYnYxeEEvVnhCdWZ6V3dtMmlGR0I0M0lLRkM1NlZTM0FWS01EcElLekE9IiwibWFjIjoiNGNjOWQ1ODMzNzI3MDQ3Y2U1YmE3NTAyMDc5YTNiMjczNTZiODM0NjUyYzdlZWQ1MWE1NzdmZmNkZmM1OTYzNSIsInRhZyI6IiJ9';
        $privateKey = Crypt::decryptString($privateKey);

        $amount = bcmul($amount, pow(10, $this->decimals));

        $contract = new Contract($this->web3->provider, $this->abi);
        try {
            // 获取正确的 nonce 值
            $nonce = $this->getTransactionCount($from);

            $data = $contract->at($pair)->getData('approve', $spender, $amount);

            $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9
            $gasLimit = 800000;

            $txParams = [
                'from'     => $from, // 授权的发起账户
                'to'       => $pair, // 授权给 pair 合约地址
                'data'     => '0x'.$data, // 调用 approve 方法的数据
                'value'    => '0x0', // 不需要发送 ETH
                'gas'      => '0x'.dechex($gasLimit),
                'gasPrice' => '0x'.dechex($gasPrice),
                'nonce'    => '0x'.dechex($nonce),
                'chainId'  => '0x'.dechex($this->chainId),
            ];

            $gasLimit            = $this->estimateGas($txParams)->toString();
            $txParams['gas']     = '0x'.dechex($gasLimit);
            $txParams['chainId'] = $this->chainId;

            $tx       = new Transaction($txParams);
            $signedTx = '0x'.$tx->sign($privateKey);

            $txHash = $this->sendRawTransaction($signedTx);

            $receipt = $this->getTransactionInfo($txHash);
            //            if ($receipt === null) {
            //                throw new \Exception('Transaction failed. Transaction not found in the blockchain.');
            //            }

            // 检查交易是否成功（状态为 0x1 表示成功）
            if (isset($receipt->status) && $receipt->status !== '0x1') {
                throw new \Exception('Transaction failed.');
            }

            return ['success' => true, 'hash' => $txHash];
        } catch (Exception $e) {
            Log::error(
                json_encode([
                    'error' => 'approve error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function buChongzhi($contractAddress)
    {
        $transactionInfoByEvent = $this->syncOrder($contractAddress, 'Transfer(address,address,uint256)');

        return $transactionInfoByEvent;
    }

    public function syncOrder($contractAddress, $str)
    {
        // 设置区块时间筛选条件
        $block          = self::getBlock();
        $fromBlock      = '0x'.dechex($block - 100);
        $toBlock        = '0x'.dechex($block);
        $address        = $contractAddress;
        $transferFunc   = $str;
        $transferTopics = '0x'.Keccak::hash($transferFunc, 256);
        $topics         = [$transferTopics];
        // 查询logs
        $logs = self::getLogs($fromBlock, $toBlock, $address, $topics);

        return $logs;
    }

    public function getLogs($fromBlock, $toBlock, $address, $topics)
    {
        $logs   = [];
        $filter = [
            'fromBlock' => $fromBlock,
            'toBlock'   => $toBlock,
            'address'   => $address,
            'topics'    => $topics,
        ];
        $this->web3 = new Web3(new HttpProvider('https://bsc-testnet.core.chainstack.com/1d1f3059837f1205b4b5247bb72d97b9', 20));
        $this->web3->eth->getLogs($filter, function ($err, $res) use (&$logs) {
            if ($err === null) {
                $logs = $res;
            } else {
                var_dump($err->getMessage());
            }
        });

        return $logs;
    }

    public function getLogs2($fromBlock, $toBlock, $address, $topics)
    {
        $logs   = [];
        $filter = [
            'fromBlock' => $fromBlock,
            'toBlock'   => $toBlock,
            'address'   => $address,
            'topics'    => $topics,
        ];
        $rpc        = config('site.block_scan_rpc');
        $timeout    = config('site.block_scan_timeout', 1);
        $this->web3 = new Web3(new HttpProvider($rpc, $timeout));
        $this->web3->eth->getLogs($filter, function ($err, $res) use (&$logs) {
            if ($err === null) {
                $logs = $res;
            } else {
                Log::error("web3 service getLogs2 error: {$err->getMessage()}, line: {$err->getLine()}, file: {$err->getFile()}");
            }
        });

        return $logs;
    }

    public function getBlock(): string
    {
        $lastBlock = '';
        $this->web3->eth->blockNumber(function ($err, $res) use (&$lastBlock) {
            if ($err === null) {
                $lastBlock = $res->toString();
            }
        });

        return $lastBlock;
    }

    public function getBlock2(): string
    {
        $lastBlock  = '';
        $rpc        = config('site.block_scan_rpc');
        $timeout    = config('site.block_scan_timeout', 1);
        $this->web3 = new Web3(new HttpProvider($rpc, $timeout));
        $this->web3->eth->blockNumber(function ($err, $res) use (&$lastBlock) {
            if ($err === null) {
                $lastBlock = $res->toString();
            }
        });

        return $lastBlock;
    }
}
