<?php

namespace App\Http\Controllers\Api\User;

use App\Exceptions\ServiceException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Pelieth\LaravelEcrecover\EthSigRecover;

class UserController extends Controller
{
    /**
     * 用户登录
     *
     * @param  Request  $request  请求对象
     * @return \Illuminate\Http\JsonResponse 登录结果
     */
    public function userLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address'       => 'required|regex:/^(0x)?[0-9a-fA-F]{40}$/',
            'invite_code'   => 'nullable|regex:/^[0-9]{8}$/',
            'place_address' => 'nullable|regex:/^(0x)?[0-9a-fA-F]{40}$/',
            'signature'     => 'required',
            'message'       => 'required',
            'random'        => 'required|regex:/^[a-zA-Z0-9]{6}$/',
        ], [
            'address.required'  => __('user.message.address_required'),
            'address.regex'     => __('user.message.address_error'),
            'invite_code.regex' => __('邀请码不正确'),
            'random.required'   => __('随机字符串不能为空'),
            'random.regex'      => __('随机字符串格式不正确'),
        ]);

        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }
        $address    = $request->input('address');
        $inviteCode = $request->input('invite_code');
        $signature  = $request->input('signature');
        $message    = $request->input('message');
        $random     = $request->input('random');

        // 检测是否是默认用户，是的话拒绝登录
        $system_address = config('site.system_address', []);
        if (in_array($address, $system_address)) {
            return apiError('invalid address', 10402);
        }

        if (config('app.check.login')) {
            // 验证签名消息格式：
            // 消息格式："XXX Login XXXXXX1234567890"
            // 消息后10个字符：时间戳
            // 中间6个字符：随机字符串
            if (strlen($message) <= 10) {
                return apiError('invalid signature message', 10401);
            }
            $time = substr($message, -10);
            if (! preg_match('/^\d{10}$/', $time)) {
                return apiError('invalid signature time', 10401);
            }
            $sign_msg = config('app.name').' Login '.$random.$time;
            if ($message !== $sign_msg) {
                return apiError('invalid signature message', 10401);
            }
            $diff_time = Carbon::now()->timestamp - intval($time);
            if (abs($diff_time) > 300) {
                Log::error('user login signature message expired: '.$diff_time);

                return apiError('signature message expired', 10401);
            }

            try {
                $eth_sig_util     = new EthSigRecover();
                $recoveredAddress = $eth_sig_util->personal_ecRecover($message, $signature);
                if (strtolower($recoveredAddress) !== strtolower($address)) {
                    Log::error('user login signature error1: '.$recoveredAddress);

                    return apiError('invalid signature', 10401);
                }
            } catch (\Exception $e) {
                Log::error('user login signature error2');

                return apiError('invalid signature', 10401);
            }
        }

        try {
            $info = UserService::userLogin($address, $inviteCode);

            return apiSuccess($info, __('Login success'));
        } catch (ServiceException $e) {
            return $e->render($request);
        }
    }

    // 是否新用户
    public function isNewUser(Request $request)
    {
        $address   = $request->input('address');
        $isNewUser = UserService::isNewUser($address);

        return apiSuccess($isNewUser);
    }

    // 创建新用户【测试用】
    public function newUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address'     => 'required|regex:/^(0x)?[0-9a-fA-F]{40}$/',
            'invite_code' => 'nullable|integer',
        ], [
            'address.required'    => 'Address is required',
            'address.regex'       => 'Invalid address',
            'invite_code.integer' => 'Invalid invite code',
        ]);

        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $address     = $request->input('address');
        $invite_code = $request->input('invite_code');

        if (User::query()->where('address', $address)->exists()) {
            return apiError('User already exists');
        }

        $parent = null;
        if ($invite_code) {
            $parent = User::query()->where('invite_code', $invite_code)->first();
            if (! $parent) {
                return apiError('Invalid invite code');
            }
        }

        try {
            $user = UserService::newUser($address, $parent ? $parent->id : 0);
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }

        return apiSuccess([
            'id'          => $user->id,
            'address'     => $user->address,
            'name'        => $user->name,
            'avatar'      => $user->avatar,
            'invited_uid' => $user->invited_uid,
            'invite_code' => $user->invite_code,
        ]);
    }
}
