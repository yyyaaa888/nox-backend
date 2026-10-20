<?php

namespace App\Services;

use App\Models\Chain;
use Illuminate\Support\Facades\Log;
use phpseclib\Math\BigInteger;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;

class EthService
{
    private $host; // rpc地址

    private $web3; // web3对象

    private $eth = null;

    private $chainId;

    protected $decimals;

    // protected $gasLimit;
    // protected $gasPrice;
    private $from = null;

    private $privateKey;

    public function __construct(Chain $chain, ?string $from = null, ?string $privateKey = null)
    {
        $this->host     = $chain['rpc_url'];
        $this->chainId  = $chain['chain_id'];
        $this->decimals = $chain['decimals'];
        // $this->gasPrice = $chain['gas_price'];
        // $this->gasLimit = $chain['gas_limit'];
        $this->web3       = new Web3(new HttpProvider($this->host, 10));
        $this->eth        = $this->web3->eth;
        $this->from       = $from;
        $this->privateKey = $privateKey;
    }

    /**
     * 获取指定账户的余额
     *
     * @param  string  $account  以太坊账户地址
     * @return string 返回账户余额
     *
     * @throws \Exception 如果获取余额时发生错误
     */
    public function getBalance($address)
    {
        $balance = '';
        $this->eth->getBalance($address, function ($err, $amount) use (&$balance) {
            if ($err !== null) {
                throw new \Exception('Error getting balance: '.$err->getMessage());
            }
            $balance = $amount;
        });

        return $balance;
    }

    public function getTransactionCount($address)
    {
        $count = '';
        $this->eth->getTransactionCount($address, function ($err, $result) use (&$count) {
            if ($err !== null) {
                throw new \Exception('Error getting transaction count: '.$err->getMessage());
            }
            $count = $result;
        });

        return is_object($count) ? $count->toString() : $count; // 确保返回的是十进制的 nonce 值
    }

    public function gasPrice()
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

    /**
     * 估算执行特定交易所需的 gas 数量
     */
    public function estimateGas($txParams)
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
        $hash = '';
        $this->eth->sendRawTransaction($signedTx, function ($err, $txHash) use (&$hash) {
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

    public function getSignedTx(array $txParams): string
    {
        $tx = new Transaction($txParams);

        return '0x'.$tx->sign($this->privateKey);
    }

    public function getDataForTransfer($contract, $address, $amount, $abi)
    {
        $contract = new Contract($this->web3->provider, $abi);

        return '0x'.$contract->at($contract)->getData('transfer', $address, $amount);
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
            $transaction = null;
            // Get the transaction receipt using the transaction hash
            $this->eth->getTransactionReceipt($txHash, function ($err, $receipt) use (&$transaction) {
                if ($err !== null) {
                    throw new \Exception('Error getting transaction receipt: '.$err->getMessage());
                }

                // Check if the transaction exists and has a successful status
                if ($receipt && $receipt->status === '0x1') {
                    $transaction = $receipt; // Return the transaction information
                }
            });

            return $transaction;
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'txHash' => $txHash,
                    'error'  => 'EthService getTransactionInfo error: '.$e->getMessage(),
                ])
            );

            return null;
        }
    }

    public function getTransactionByHash(string $txHash)
    {
        try {
            $transaction = null;
            $this->eth->getTransactionByHash($txHash, function ($err, $receipt) use (&$transaction) {
                if ($err !== null) {
                    throw new \Exception('Error getting transaction: '.$err->getMessage());
                }
                $transaction = $receipt;
            });

            return $transaction;
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'txHash' => $txHash,
                    'error'  => 'EthService getTransactionByHash error: '.$e->getMessage(),
                ])
            );

            return null;
        }
    }

    public function sendTransaction(string $to, string $value)
    {
        try {
            if (! $this->from) {
                throw new \Exception('From address is empty.');
            }
            if (! $to) {
                throw new \Exception('To address is empty.');
            }

            $value = bcmul($value, bcpow(10, $this->decimals));

            $nonce    = $this->getTransactionCount($this->from);
            $txParams = [
                'from'    => $this->from,
                'to'      => $to,
                'value'   => '0x'.gmp_strval(gmp_init($value), 16),
                'nonce'   => '0x'.dechex($nonce),
                'chainId' => '0x'.dechex($this->chainId),
            ];

            $gasPrice = $this->gasPrice();
            $gasLimit = $this->estimateGas($txParams);

            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

            $balance = $this->getBalance($this->from);
            if (bccomp($balance, $totalGasFee) < 0) {
                throw new \Exception('Insufficient funds for gas * price + value.');
            }

            $txParams['gas']      = '0x'.dechex($gasLimit);
            $txParams['gasPrice'] = '0x'.dechex($gasPrice);
            $txParams['chainId']  = $this->chainId;

            $signedTx = $this->getSignedTx($txParams);
            $txHash   = $this->sendRawTransaction($signedTx);

            return $this->checkTx($txHash);
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'data' => [
                        'from'   => $this->from,
                        'to'     => $to,
                        'amount' => $value,
                    ],
                    'error' => 'EthService sendTransaction error: '.$e->getMessage(),
                ])
            );

            return ['success' => false, 'hash' => null, 'message' => $e->getMessage()];
        }
    }

    public function sendTransaction2(string $to, string $value)
    {
        try {
            if (! $this->from) {
                throw new \Exception('From address is empty.');
            }
            if (! $to) {
                throw new \Exception('To address is empty.');
            }

            $value = bcmul($value, bcpow(10, $this->decimals));

            $nonce    = $this->getTransactionCount($this->from);
            $txParams = [
                'from'    => $this->from,
                'to'      => $to,
                'value'   => '0x'.gmp_strval(gmp_init($value), 16),
                'nonce'   => '0x'.dechex($nonce),
                'chainId' => '0x'.dechex($this->chainId),
            ];

            $gasPrice = $this->gasPrice();
            $gasLimit = $this->estimateGas($txParams);

            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

            $balance = $this->getBalance($this->from);
            if (bccomp($balance, $totalGasFee) < 0) {
                throw new \Exception('Insufficient funds for gas * price + value.');
            }

            $txParams['gas']      = '0x'.dechex($gasLimit);
            $txParams['gasPrice'] = '0x'.dechex($gasPrice);
            $txParams['chainId']  = $this->chainId;

            $signedTx = $this->getSignedTx($txParams);
            $txHash   = $this->sendRawTransaction($signedTx);

            return ['success' => true, 'hash' => $txHash];
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'data' => [
                        'from'   => $this->from,
                        'to'     => $to,
                        'amount' => $value,
                    ],
                    'error' => 'EthService sendTransaction2 error: '.$e->getMessage(),
                ])
            );

            return ['success' => false, 'hash' => null, 'message' => $e->getMessage()];
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
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'data' => [
                        'hash' => $hash,
                    ],
                    'error' => 'EthService checkTx error: '.$e->getMessage().' - '.$e->getTraceAsString(),
                ])
            );

            return ['success' => false, 'hash' => $hash, 'message' => 'checkTx failed: please contact support'];
        }
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
        $receipt = $this->getTransactionInfo($txHash);
        if ($receipt) {
            $transactionInfo = $this->getTransactionByHash($txHash);
            $data            = new \stdClass();
            if ($transactionInfo) {
                $data->amount = bchexdec($transactionInfo->value);
                $data->from   = $transactionInfo->from;
                $data->to     = $transactionInfo->to;

                // $data->nonce = bchexdec($transactionInfo->nonce);
                // $data->chainId = bchexdec($transactionInfo->chainId);
                return $data;
            }
        }

        return false;
    }

    public function verifyTransactionHash2($txHash)
    {
        return $this->verifyTransactionHash($txHash);
    }

    public function withdrawToken(string $contract, string $to, $amount, $abi)
    {
        if (! $this->from) {
            return ['success' => false, 'message' => 'Withdraw failed: from address is empty'];
        }
        try {
            $amount = bcmul($amount, pow(10, $this->decimals));

            // 获取正确的 nonce 值
            $nonce = $this->getTransactionCount($this->from);

            $rawTransactionData = $this->getDataForTransfer($contract, $to, $amount, $abi);

            $gasPrice = 1000000000; // 将 1 Gwei 转换为 wei 10^9
            $gasLimit = 800000;

            $txParams = [
                'nonce'    => '0x'.dechex($nonce),
                'from'     => $this->from,
                'to'       => $contract,
                'gas'      => '0x'.dechex($gasLimit),
                'gasPrice' => '0x'.dechex($gasPrice), // gas费用
                'value'    => '0x0',
                'chainId'  => '0x'.dechex($this->chainId),
                'data'     => $rawTransactionData,
            ];

            // 估算 gas
            $gasLimit = $this->estimateGas($txParams);

            // 计算总的 gas fee
            $totalGasFee = bcmul($gasLimit, $gasPrice, 0);

            // 检查余额是否足够
            $balance = $this->getBalance($this->from);
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
                // throw new \Exception('Transaction not found in the blockchain.');
            }

            // 检查交易是否成功
            if (isset($receipt['status']) && $receipt['status'] !== '0x1') {
                throw new \Exception('Transaction failed.');
            }

            return ['success' => true, 'hash' => $txHash];
        } catch (\Exception $e) {
            Log::error(
                json_encode([
                    'data' => [
                        'contract' => $contract,
                        'to'       => $to,
                        'amount'   => $amount,
                    ],
                    'error' => 'Ethservice withdrawToken error: '.$e->getMessage(),
                ])
            );

            return ['success' => false, 'message' => 'Withdraw failed: please contact support'];
        }
    }
}
