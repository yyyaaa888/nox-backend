<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Jobs\UserUpgradeJob;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserAsset as UserAssetsModel;
use App\Models\UserData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * 用户登录
     *
     * @param  string  $address  用户地址
     * @param  string  $inviteCode  邀请码
     * @return array 用户信息
     *
     * @throws ServiceException
     */
    public static function userLogin(string $address, ?string $inviteCode = null)
    {
        $address = strtolower(self::checkParams($address));

        // 禁止登录
        if (getGlobalConfig('not_allow_to_user_login') == 1) {
            throw new ServiceException(__('Login error'));
        }

        // 验证地址
        if (! self::isValidAddress($address)) {
            throw new ServiceException(__('Address error'));
        }
        // 验证邀请码
        $pid = 0;
        if ($inviteCode) {
            $inviteInfo = User::query()->where('invite_code', $inviteCode)->first();
            if (! $inviteInfo) {
                throw new ServiceException(__('邀请码输入错误，请输入正确的邀请码'), 10402);
            }
            $pid = $inviteInfo->id;
        }

        // 查询地址信息
        $findInfo = User::query()->where('address', $address)->first();
        if (! $findInfo) {
            if (empty($inviteCode)) {
                throw new ServiceException(__('请输入邀请码'), 10402);
            }
            // 验证邀请码是否存在没有邀请码不能登录
            if (! $pid) {
                throw new ServiceException(__('邀请码错误'), 10402);
            }

            return self::registerNewUser($address, $pid);
        } else {
            // // 判断是否有上级 没有上级不可以登录
            // if (!$pid) {
            //     throw new ServiceException(__('邀请码错误'),10402);
            // }

            return self::handleExistingUser($findInfo);
        }
    }

    /**
     * 验证地址格式
     *
     * @param  string  $address  用户地址
     * @return bool 是否有效
     */
    private static function isValidAddress($address)
    {
        return preg_match('/^(0x)?[0-9a-fA-F]{40}$/', $address);
    }

    /**
     * 注册新用户
     *
     * @param  string  $address  用户地址
     * @param  int  $pid  邀请人ID
     * @return array 用户信息
     *
     * @throws \Exception
     * @throws ServiceException
     */
    public static function registerNewUser(string $address, int $pid)
    {
        DB::beginTransaction();
        try {
            // 随机生成1-5的数字
            $num   = rand(1, 5);
            $count = 0;
            while (true) {
                $invite_code = createCode('', 7);
                $count++;
                if (User::query()->where('invite_code', $invite_code)->exists()) {
                    if ($count > 10) {
                        throw new \Exception('create invite code fail');
                    }

                    continue;
                }
                break;
            }
            $user = User::create([
                'address'     => $address,
                'name'        => self::hideStr($address),
                'invite_code' => $invite_code,
                'invited_uid' => $pid,
                'avatar'      => '/static/images/public/avatar'.$num.'.png',
            ]);

            // // 创建资产
            // self::createUserAsset($user->id);

            // 创建用户数据
            self::createUserData($user->id);

            // 更新用户关系
            (new UserRelationService())->setupUserRelations($user->id, $pid);

            // 设置token
            self::setUserToken($user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('registerNewUser error: '.$e->getMessage());
            throw new ServiceException(__('registration_failed'));
        }

        $user_id = $user->id;
        UserUpgradeJob::dispatch($user_id);

        return self::generateUserResponse($user);
    }

    /**
     * 系统创建新用户
     *
     * @param  string  $address  用户地址
     * @param  int  $pid  邀请人ID
     * @return array 用户信息
     *
     * @throws \Exception
     * @throws ServiceException
     */
    public static function sysNewUser(string $address, int $pid, array $param)
    {
        $address = strtolower(self::checkParams($address));
        DB::beginTransaction();
        try {
            // 随机生成1-5的数字
            $num   = rand(1, 5);
            $count = 0;
            while (true) {
                $invite_code = createCode('', 7);
                $count++;
                if (User::query()->where('invite_code', $invite_code)->exists()) {
                    if ($count > 10) {
                        throw new \Exception('create invite code fail');
                    }

                    continue;
                }
                break;
            }
            $user = User::create([
                'address'     => $address,
                'name'        => self::hideStr($address),
                'invite_code' => $invite_code,
                'invited_uid' => $pid ?: 0,
                'avatar'      => '/static/images/public/avatar'.$num.'.png',
                'status'      => $param['status'] ? 1 : 0,
                'remark'      => $param['remark'] ?: '',
            ]);

            // // 创建资产
            // self::createUserAsset($user->id);

            // 创建用户数据
            self::createUserData($user->id);

            // 更新用户关系
            (new UserRelationService())->setupUserRelations($user->id, $pid);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('sysNewUser error: '.$e->getMessage());
            throw new ServiceException('系统创建新用户失败');
        }

        return $user;
    }

    /**
     * 处理已存在用户
     *
     * @param  User  $user  用户对象
     * @return array 用户信息
     *
     * @throws ServiceException
     */
    private static function handleExistingUser($user)
    {
        if ($user->status == User::USER_STATUS_FORBIDDEN) {
            throw new ServiceException(__('Address error'));
        }

        // // 创建资产
        // self::createUserAsset($user->id);

        // 创建用户数据
        self::createUserData($user->id);

        // 设置token
        self::setUserToken($user);

        return self::generateUserResponse($user);
    }

    /**
     * 设置用户token
     *
     * @param  User  $user  用户对象
     */
    private static function setUserToken($user)
    {
        $user->tokens()->delete();
        $token       = $user->createToken(createCode('G', 10), ['expires_in' => 86400])->plainTextToken;
        $user->token = $token;
        User::query()->where('id', $user->id)->update(['remember_token' => $token]);
    }

    /**
     * 生成用户响应信息
     *
     * @param  User  $user  用户对象
     * @return array 用户信息
     */
    private static function generateUserResponse($user)
    {
        return [
            'id'          => $user->id,
            'address'     => $user->address,
            'name'        => $user->name,
            'token'       => $user->token,
            'invite_code' => $user->invite_code,
            'invited_uid' => $user->invited_uid,
        ];
    }

    /**
     * 检查参数
     *
     * @param  string  $str  参数字符串
     * @return string 处理后的字符串
     */
    public static function checkParams(&$str)
    {
        $arr = [
            '/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU',
            "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
            "/select\b|insert\b|update\b|delete\b|drop\b|;|\"|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is",
        ];
        $str = preg_replace($arr, '', $str);
        $str = strip_tags($str);
        $str = str_replace(' ', '', $str);

        return $str;
    }

    /**
     * 隐藏字符串的一部分
     *
     * @param  string  $string  原字符串
     * @param  int  $start  开始位置
     * @param  int  $length  隐藏长度
     * @param  string  $re  替换字符
     * @return string 处理后的字符串
     */
    public static function hideStr($string, $start = 4, $length = -4, $re = '*')
    {
        if (empty($string)) {
            return false;
        }
        $strarr    = [];
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {
            $strarr[]  = mb_substr($string, 0, 1, 'utf8');
            $string    = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin  = $start >= 0 ? $start : ($strlen - abs($start));
        $end    = $last = $strlen - 1;
        if ($length > 0) {
            $end = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }
        for ($i = $begin; $i <= $end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin >= $end || $begin >= $last || $end > $last) {
            return false;
        }
        $strarr = implode('', $strarr);
        $strarr = str_replace('***************', '', $strarr);

        return $strarr;
    }

    /**
     * 创建用户资产
     *
     * @param  int  $user_id  用户ID
     */
    public static function createUserAsset($user_id)
    {
        $assets = Asset::getAllAsset();
        if ($assets) {
            $existingAssets = UserAssetsModel::query()->where('uid', $user_id)
                ->pluck('assets_id')
                ->toArray();
            foreach ($assets as $assetId => $asset) {
                if (! in_array($assetId, $existingAssets)) {
                    UserAssetsModel::addUserAssets($user_id, $assetId);
                }
            }
        }
    }

    /**
     * 创建用户数据
     *
     * @param  int  $user_id  用户ID
     */
    public static function createUserData($user_id)
    {
        $user = UserData::getInfo($user_id);
        if (! $user) {
            UserData::addInfo($user_id);
        }
    }

    /** 获取我的父代 */
    public static function getMyParents($user_id)
    {
        $info = User::query()->find($user_id);
        if ($info && $info->invited_uid > 0) {
            $total[] = $info->invited_uid;
            $info    = User::query()->where('id', $info->invited_uid)->first();
        } else {
            return 0;
        }
        $res = self::getMyParents($info->id);
        if ($res) {
            $total = array_merge($total, $res);
        }

        return $total;
    }

    // 是否新用户
    public static function isNewUser($address)
    {
        return ! User::query()->where('address', $address)->exists();
    }

    /**
     * 创建新用户
     *
     * @param  string  $address  用户地址
     * @param  int  $pid  邀请人ID
     * @return User 用户信息
     *
     * @throws \Exception
     * @throws ServiceException
     */
    public static function newUser(string $address, int $pid)
    {
        DB::beginTransaction();
        try {
            // 随机生成1-5的数字
            $num  = rand(1, 5);
            $user = User::create([
                'address'     => $address,
                'name'        => self::hideStr($address),
                'invite_code' => createCode('', 7),
                'invited_uid' => $pid,
                'avatar'      => '/static/images/public/avatar'.$num.'.png',
            ]);

            // 创建用户数据
            self::createUserData($user->id);

            // 更新用户关系
            (new UserRelationService())->setupUserRelations($user->id, $pid);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollback();
            throw new \LogicException($exception->getMessage());
        }

        return $user;
    }
}
