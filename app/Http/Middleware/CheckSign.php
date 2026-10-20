<?php

namespace App\Http\Middleware;

use App\Exceptions\LogicException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckSign
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
        // // 仅在生产环境中启用
        // if (!app()->environment('production')) {
        //     return $next($request);
        // }
        if (! config('app.check.sign')) {
            return $next($request);
        }

        $sign  = request()->header('sign');
        $times = request()->header('times');
        $token = $request->header('authorization');
        if (! $sign || ! $times || ! $token) {
            return apiError('illegal request', 10400);
        }
        if (! is_numeric($times)) {
            return apiError('illegal request', 10400);
        }
        try {
            self::checkSign($token, $sign, $times);
        } catch (LogicException $e) {
            return apiError('illegal request: '.$e->getMessage(), 10400);
        } catch (\Exception $e) {
            return apiError('illegal request', 10400);
        }

        return $next($request);
    }

    protected function checkSign($token, $sign, $time)
    {
        $now       = time();
        $secretKey = env('SIGN_KEY');
        if (! $secretKey) {
            throw new LogicException('sign key is empty');
        }
        $checkHash = hash_hmac('sha256', $token.$time, $secretKey);
        $v         = abs($now - $time / 1000);
        if ($v > env('SIGN_TIME_OUT', 6)) {
            Log::info('sign timeout: '.$v);
            throw new LogicException('sign timeout');
        }
        if (strtolower($sign) != strtolower($checkHash)) {
            Log::info('sign error');
            throw new LogicException('sign error');
        }
    }
}
