<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DecryptAndVerifyRsaMessage
{
    /**
     * 处理传入的请求
     *
     * @param  \Illuminate\Http\Request  $request  请求对象
     * @param  \Closure  $next  下一个中间件
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $str       = request()->path();
        $sign      = request()->header('sign');
        $times     = request()->header('times');
        $token     = $request->header('authorization');
        $checkSign = self::checkSign($token, $sign, $times);
        if (! $checkSign) {
            return apiError('illegal request', 10400);
        }

        return $next($request);

        // 仅在生产环境中启用
        if (env('APP_ENV') !== 'production') {
            return $next($request);
        }

        $encryptedMessage = $request->input('message');
        if (! $encryptedMessage) {
            return apiError('Encrypted message is required', 10400);
        }

        // 从配置文件中获取私钥
        $privateKey         = Storage::get('private_key.pem');
        $privateKeyResource = openssl_pkey_get_private($privateKey);

        if (! $privateKeyResource) {
            return apiError('Invalid private key', 10500);
        }

        $decryptedMessage  = '';
        $decryptionSuccess = openssl_private_decrypt(base64_decode($encryptedMessage), $decryptedMessage, $privateKeyResource);

        if (! $decryptionSuccess) {
            return apiError('Invalid encrypted message', 10400);
        }
        $decryptedMessage = json_decode($decryptedMessage, true);
        if ($decryptedMessage) {
            // 将解密后的消息参数添加到请求对象中
            $request->merge($decryptedMessage);
        }
        // 从请求对象中移除message参数
        $request->request->remove('message');

        return $next($request);
    }

    public function handleRequestData($data)
    {
        ksort($data);
        $stringToSign = implode('_', $data);

        return $stringToSign;
    }

    public function verifyTime($time)
    {
        $currentTime    = time();
        $timeDifference = abs($currentTime - $time);
        // 判断时间差是否在 30 秒内
        if ($timeDifference > 30) {
            return apiError('Authentication failed timeout.', '', 402);
        }
    }

    protected function checkSign($token, $sign, $time)
    {
        $now       = time();
        $secretKey = 'gVTtd0nhu2RMO3n4yvZJGMPe3nKCtmQBJ11b';
        $checkHash = hash_hmac('sha256', $token.$time, $secretKey);
        $v         = abs($now - $time / 1000);
        if (strtolower($sign) == strtolower($checkHash) && $v < env('SIGN_TIME_OUT', 30)) {
            return true;
        } else {
            return false;
        }
    }
}
