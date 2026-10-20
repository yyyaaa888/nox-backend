<?php

namespace App\Http\Controllers\Api\User;

use App\Exceptions\LogicException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PayPasswordController extends Controller
{
    const CACHE_KEY = 'user_pay_password_';

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iv_key'       => 'required|string',
            'iv_data'      => 'required|string',
            'iv_timestamp' => 'required|integer',
            'iv_sign'      => 'required|string',
            'iv_nonce'     => 'required|string',
        ], [
            'iv_key.required'       => __('参数 iv_key 不能为空'),
            'iv_key.string'         => __('参数 iv_key 不正确'),
            'iv_data.required'      => __('参数 iv_data 不能为空'),
            'iv_data.string'        => __('参数 iv_data 不正确'),
            'iv_timestamp.required' => __('参数 iv_timestamp 不能为空'),
            'iv_timestamp.integer'  => __('参数 iv_timestamp 不正确'),
            'iv_sign.required'      => __('参数 iv_sign 不能为空'),
            'iv_sign.string'        => __('参数 iv_sign 不正确'),
            'iv_nonce.required'     => __('参数 iv_nonce 不能为空'),
            'iv_nonce.string'       => __('参数 iv_nonce 不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $iv_key       = $request->input('iv_key');
        $iv_data      = $request->input('iv_data');
        $iv_timestamp = $request->input('iv_timestamp');
        $iv_sign      = $request->input('iv_sign');
        $iv_nonce     = $request->input('iv_nonce');

        $user    = $request->user();
        $user_id = $user->id;

        // 检查签名
        $params = [
            'iv_key'  => $iv_key,
            'iv_data' => $iv_data,
        ];
        $this->checkSign($params, $iv_sign, $iv_timestamp, $iv_nonce);

        $lock = Cache::lock(self::CACHE_KEY.$user_id, 15);
        if (! $lock->get()) {
            return apiError(__('系统繁忙，请稍后再试'));
        }

        // 判断是否是异常用户
        if ($user->status == 0) {
            $lock->release();

            return apiError(__('系统繁忙，请稍后再试'));
        }

        // 检查密码是否已设置
        if (! empty($user->pay_password)) {
            $lock->release();

            return apiError(__('支付密码已设置'));
        }

        $privateKeyPath = storage_path('keys/private_key.pem');
        $privateKey     = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if (! $privateKey) {
            $lock->release();

            return apiError(__('私钥加载失败'));
        }

        // 用 RSA 私钥解密，还原出前端的 AES 密钥
        $aesKey = '';
        // base64_decode 是因为前端 JSEncrypt 默认输出的是 Base64 字符串
        openssl_private_decrypt(base64_decode($iv_key), $aesKey, $privateKey);

        if (! $aesKey) {
            $lock->release();

            return apiError(__('AES密钥加载失败'));
        }

        // 用还原出的 AES 密钥，解密出真实的支付密码
        $decryptedPassword = openssl_decrypt(
            base64_decode($iv_data),
            'AES-128-ECB', // 对应前端的 AES 模式和位数
            hex2bin($aesKey),
            OPENSSL_RAW_DATA
        );

        if (! $decryptedPassword) {
            $lock->release();

            return apiError(__('支付密码解密失败'));
        }

        // 保存支付密码到数据库
        $user->pay_password = Hash::make($decryptedPassword);
        $user->save();
        $lock->release();

        return apiSuccess(__('支付密码设置成功'));
    }

    protected function checkSign(array $params, string $iv_sign, int $iv_timestamp, string $iv_nonce)
    {
        $params['iv_timestamp'] = $iv_timestamp;
        $params['iv_nonce']     = $iv_nonce;
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $key.'='.urlencode($value).'&';
        }

        $now       = time();
        $secretKey = env('SIGN_KEY');
        if (! $secretKey) {
            throw new LogicException('sign key is empty');
        }
        $checkHash = hash_hmac('sha256', $signStr, $secretKey);
        $v         = abs($now - $iv_timestamp);
        if ($v > env('SIGN_TIME_OUT', 6)) {
            throw new LogicException('sign timeout');
        }
        if (strtolower($iv_sign) != strtolower($checkHash)) {
            throw new LogicException('sign error');
        }
        // 校验nonce是否重复
        $nonce = $params['iv_nonce'];
        $nonce = $nonce.$iv_timestamp;
        $nonce = md5($nonce);
        if (Cache::has($nonce)) {
            throw new LogicException('nonce repeat');
        }
        Cache::put($nonce, 1, env('SIGN_TIME_OUT', 6));
    }
}
