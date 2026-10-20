<?php

namespace App\Services\Web3;

use Elliptic\EC;
use kornrunner\Keccak;

class SignService
{
    public function signAdd(string $address, string $contractAddress, string $amount, string $money_type, string $privateKey)
    {
        $amount   = (string) $amount; // 字符串金额,支持小数,方法内默认币种精度18
        $currency = 2; // 币种 1 BNB 2 其他代币
        if ($money_type == 'BNB') {
            $currency = 1;
        } elseif ($money_type == 'Redeem') {
            $currency = 3; // 赎回
        }
        $contractAddress = ($money_type == 'BNB') ? '0x0000000000000000000000000000000000000000' : $contractAddress;
        $time            = time();

        $message = $this->signDetail($address, $contractAddress, $amount, $currency, $time);
        //        echo($message);

        //        $add = env('APP.SIGN_ADDRESS');//后台使用钱包地址
        // $privateKey = env('WITHDRAW_SIGN_PRIVATEKEY'); //请替换为实际的私钥
        $signature = $this->personalSign($message, $privateKey);

        return ['sign' => $signature, 'message' => $message, 'amount' => $amount];
    }

    public function signDetail($address, $contractAddress, $amount, $currency, $timestamp)
    {
        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new \Exception('无效的地址');
        }
        if (! is_string($amount) || ! preg_match('/^\d+(\.\d+)?$/', $amount)) {
            throw new \Exception('金额必须是字符串形式的数字，可以包含小数点');
        }
        $amountParts          = explode('.', $amount);
        $integerPart          = $amountParts[0];
        $fractionalPart       = isset($amountParts[1]) ? str_pad($amountParts[1], 18, '0', STR_PAD_RIGHT) : str_repeat('0', 18);
        $amountInSmallestUnit = bcadd($integerPart.$fractionalPart, '0', 0);
        $maxAmount            = '340282366920938463463374607431768211455';
        if (bccomp($amountInSmallestUnit, $maxAmount, 0) > 0) {
            throw new \Exception('代币金额超过允许的最大值');
        }
        if (strlen($currency) !== 1) {
            throw new \Exception('币种必须是1个字符');
        }
        if (! is_int($timestamp) || $timestamp <= 0) {
            throw new \Exception('无效的时间戳');
        }
        $hexAmount          = str_pad(gmp_strval(gmp_init($amountInSmallestUnit, 10), 16), 32, '0', STR_PAD_LEFT);
        $hexAmount          = substr($hexAmount, -32);
        $hexAddress         = substr($address, 2);
        $hexcontractAddress = substr($contractAddress, 2);
        $hexCurrency        = str_pad(bin2hex($currency), 4, '0', STR_PAD_RIGHT);
        $hexTimestamp       = str_pad(dechex($timestamp), 8, '0', STR_PAD_LEFT);
        $message            = $hexAddress.$hexcontractAddress.$hexAmount.$hexCurrency.$hexTimestamp;

        return '0x'.$message;
    }

    public function personalSign($message, $privateKey)
    {
        if (substr($message, 0, 2) === '0x') {
            $message = substr($message, 2);
        }

        $prefix          = "\x19Ethereum Signed Message:\n".(strlen($message) / 2);
        $prefixedMessage = $prefix.hex2bin($message);
        $hash            = Keccak::hash($prefixedMessage, 256);
        $ec              = new EC('secp256k1');
        $key             = $ec->keyFromPrivate($privateKey);
        $signature       = $key->sign($hash, ['canonical' => true]);

        $r = str_pad(gmp_strval($signature->r->toString(), 16), 64, '0', STR_PAD_LEFT);
        $s = str_pad(gmp_strval($signature->s->toString(), 16), 64, '0', STR_PAD_LEFT);
        $v = dechex($signature->recoveryParam + 27);

        return '0x'.$r.$s.$v;
    }
}
