<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

// CREATE TABLE `assets` (
//     `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
//     `chain_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '区块链id',
//     `name`     varchar(50) NOT NULL COMMENT '币种单位',
//     `title`    varchar(50) NOT NULL COMMENT '币种标题',
//     `code`     varchar(32) NOT NULL COMMENT '币种CODE',
//     `decimals`      tinyint(4) NOT NULL DEFAULT '18' COMMENT '精度',
//     `type`          tinyint(3) NOT NULL DEFAULT '1' COMMENT '类型 1=主币 2=代币',
//     `status`        tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0=禁用  1=启用',
//     `is_exchange`   tinyint(1) DEFAULT '1' COMMENT '是否开启兑换',
//     `exchange_rate` decimal(19,8) DEFAULT '0.00000000' COMMENT '汇率 单位USDT(1个代币=多少USDT)',
//     `contract_abi`     text NOT NULL COMMENT '币种的token_abi',
//     `contract_address` varchar(100) NOT NULL DEFAULT '' COMMENT '代币合约地址',
//     `withdraw_address` varchar(100) NOT NULL DEFAULT '' COMMENT '提现钱包地址',
//     `withdraw_key`     varchar(1000) NOT NULL DEFAULT '' COMMENT '提现钱包加密私钥',
//     `wallet_address`   varchar(100) NOT NULL DEFAULT '' COMMENT '收款钱包地址',
//     `created_at`       datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`       datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY `chain_id` (`chain_id`),
//     KEY `code` (`code`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统币种表';

/**
 * @property int $id
 * @property int $chain_id
 * @property string $name
 * @property string $title
 * @property string $code
 * @property int $decimals
 * @property int $type
 * @property int $status
 * @property int $is_exchange
 * @property string $exchange_rate
 * @property string $contract_abi
 * @property string $contract_address
 * @property string $withdraw_address
 * @property string $withdraw_key
 * @property string $wallet_address
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Asset extends BaseModel
{
    use HasDateTimeFormatter;
    use HasFactory;

    protected $table = 'assets';

    protected $guarded = [];

    public const CODE_USDT = 'USDT'; // USDT
    public const CODE_NOX  = 'NOX';  // NOXO

    public const CODE_MAP = [
        self::CODE_USDT => 'USDT',
        self::CODE_NOX  => 'NOXO',
    ];

    public const TYPE_MAIN  = 1;
    public const TYPE_TOKEN = 2;

    public const TYPE_MAP = [
        self::TYPE_MAIN  => '主币',
        self::TYPE_TOKEN => '代币',
    ];

    protected $hidden = [
        //        'withdraw_address',
        //        'withdraw_key',
    ];

    public const ASSETS_STATUS_NO  = 0;
    public const ASSETS_STATUS_YES = 1;

    public static $status = [
        self::ASSETS_STATUS_NO  => '禁用',
        self::ASSETS_STATUS_YES => '正常',
    ];

    // 币种id
    public const ASSETS_TOKENS = [
        'token' => 2,
        'usdt'  => 3,
    ];

    /**
     * @return Asset|null
     *                    获取资产信息
     */
    public static function getAssetInfo($id): ?Asset
    {
        return Asset::query()->where('id', $id)->first();
    }

    public static function getInfoByName($name): ?Asset
    {
        return Asset::query()->where('name', $name)->first();
    }

    /**
     * @return array
     *               查看所有资产
     */
    public static function getAllAsset()
    {
        return Asset::query()->where('status', 1)
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getPayInfo(string $code, int $chain_id): ?Asset
    {
        $asset = Asset::query()->where('code', $code)->where('chain_id', $chain_id)->first();
        if (empty($asset)) {
            return null;
        }

        if ($code == Asset::CODE_USDT) {
            $asset->wallet_address   = config('site.asset.USDT.pay_address');
            $asset->withdraw_address = config('site.asset.USDT.withdraw_address');
            $asset->withdraw_key     = config('site.asset.USDT.withdraw_key');
        } elseif ($code == Asset::CODE_NOX) {
            $asset->wallet_address   = config('site.asset.NOX.pay_address');
            $asset->withdraw_address = config('site.asset.NOX.withdraw_address');
            $asset->withdraw_key     = config('site.asset.NOX.withdraw_key');
        } else {
            $asset->withdraw_key = isset($asset->withdraw_key) ? Crypt::decryptString($asset->withdraw_key) : null;
        }

        return $asset;
    }

    /**
     * 获取兑换数量
     */
    public static function getExchangeNum(string $payNum, string $payExchangeRate, string $exchangeExchangeRate, int $decimals = 18): string
    {
        if ($exchangeExchangeRate <= 0) {
            return '0';
        }
        $num = bcdiv(bcmul($payNum, $payExchangeRate, 18), $exchangeExchangeRate, $decimals);

        return removeTrailingZeros($num);
    }

    public function chain()
    {
        return $this->belongsTo(Chain::class, 'chain_id', 'chain_id');
    }
}
