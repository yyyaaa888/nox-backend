<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

class UserAsset extends BaseModel
{
    use HasDateTimeFormatter;

    protected $table = 'user_assets';

    protected $fillable = [
        'assets_id',
        'uid',
        'balance',
        'freeze_num',
        'status',
    ];

    // 是否冻结 1正常 0 冻结
    const USER_ASSET_FALSE = 0;

    const USER_ASSET_TRUE = 1;

    public static $user_status = [
        self::USER_ASSET_FALSE => '冻结',
        self::USER_ASSET_TRUE  => '正常',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'assets_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    // 根据用户，资产类型id获取单条资产信息
    public static function getUserAssetInfo($user_id, $assets_id)
    {
        return self::firstOrCreate([
            'uid'       => $user_id,
            'assets_id' => $assets_id,
        ], [
            'balance' => 0,
        ]);
    }

    /**
     * @return array
     *               根据用户id获取用户所有的资产信息
     */
    public function getAllUserAssetInfo($user_id)
    {
        return self::where('uid', $user_id)
            ->get()
            ->map(function ($item) {
                $info         = Asset::getAssetInfo($item->assets_id);
                $item['name'] = $info->name;

                return $item;
            })->toArray();
    }

    // 获取用户资产信息加锁
    public function getUserAssetInfoLock($user_id, $assets_id)
    {
        return self::where('uid', $user_id)->where('assets_id', $assets_id)->lockForUpdate()->first();
    }

    public function AssetInfo($id)
    {
        return self::where('id', $id)->first();
    }

    // 添加用户资产数据
    public static function addUserAssets($user_id, $assets_id)
    {
        UserAsset::create([
            'assets_id'  => $assets_id,
            'uid'        => $user_id,
            'balance'    => 0,
            'freeze_num' => 0,
        ]);
    }

    // 增加用户资产信息
    public static function UserIncrement($user_id, $assets_id, $number)
    {
        self::where('uid', $user_id)->where('assets_id', $assets_id)->increment('balance', $number);
    }

    // 批量增加用户资产信息
    public static function userIncrementBatch($user_arr, $assets_id, $number)
    {
        self::whereIn('uid', $user_arr)->where('assets_id', $assets_id)->increment('balance', $number);
    }

    // 减少用户资产
    public static function userDecrement($user_id, $assets_id, $number)
    {
        return self::where('uid', $user_id)->where('assets_id', $assets_id)->where('balance', '>=', $number)->decrement('balance', $number);
    }

    // 冻结用户资金
    public static function Freeze($user_id, $assets_id, $number)
    {
        self::where('uid', $user_id)->where('assets_id', $assets_id)->increment('freeze_num', $number);
    }

    /**
     * @param  $use_id
     * @return false|int
     *                   释放冻结金额
     */
    public static function ReleaseFrozen($user_id, $assets_id, $num)
    {
        $user_asset = UserAsset::where('uid', $user_id)->where('assets_id', $assets_id)->decrement('freeze_num', $num);

        return $user_asset;
    }

    // 获取用户资产
    public static function getUserAssetsByUid($uid)
    {
        return self::select('assets_id', 'balance')
            ->where('status', '1')
            ->where('uid', $uid)
            ->get()
            ->each(function (&$item) {
                // 获取币名称
                $item->assets_name = $item->assets->name;
                unset($item->assets);
            })->toArray();
    }

    public static function getUserAssets($uid, $assets_id)
    {
        return self::select('assets_id', 'balance')
            ->where('status', 1)
            ->where('uid', $uid)
            ->where('assets_id', $assets_id)
            ->first();
    }

    // 链动2+1专用

    /**
     * 扣除
     *
     * @param  $amount  金额
     * @param  $type  类型
     * @param  $msg  备注
     * @return void
     *
     * @throws \Exception
     */
    public function sub($amount, $type, $msg)
    {
        if ($this->balance_amount >= $amount) {
            $this->decrement('balance', $amount);
            if (! empty($type) && $amount > 0) {
                AssetsLog::createLog($this->uid, $this->assets_id, $amount, 2, $msg.$amount, $type);
            } else {
                throw new \Exception('余额有误');
            }
        } else {
            throw new \Exception('余额不足');
        }
    }

    /**
     * 增加金额
     *
     * @param  $amount  金额
     * @param  $type  类型
     * @param  $msg  备注
     * @return void
     *
     * @throws \Exception
     */
    public function add($amount, $type = '', $msg = '')
    {
        $this->increment('balance', $amount);
        if (! empty($type) && $amount > 0) {
            AssetsLog::createLog($this->uid, $this->assets_id, $amount, 1, $msg, $type);
        }
    }
}
