<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `recharges` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`    int(10) unsigned NOT NULL COMMENT '用户id',
//     `sn`         varchar(255)  NOT NULL COMMENT '单号',
//     `symbol`     varchar(255)  NOT NULL COMMENT '资产类型',
//     `amount`     decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '充值金额',
//     `status`     tinyint(3)    unsigned NOT NULL DEFAULT 0 COMMENT '状态 0=未支付 1=已支付 2=已取消 3=失败 4=超时',
//     `pay_at`     datetime DEFAULT NULL COMMENT '支付时间',
//     `fail_at`    datetime DEFAULT NULL COMMENT '失败时间',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`sn`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值';

/**
 * @property int $id
 * @property int $user_id
 * @property string $sn
 * @property string $symbol
 * @property string $amount
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $pay_at
 * @property \Illuminate\Support\Carbon|null $fail_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserRecharge extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'recharges';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pay_at'  => 'datetime:Y-m-d H:i:s',
            'fail_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public const STATUS_NOPAY = 0; // 未完成

    public const STATUS_PAID = 1; // 已支付

    public const STATUS_CANCEL = 2; // 已取消

    public const STATUS_FAIL = 3; // 失败

    public const STATUS_TIMEOUT = 4; // 超时

    public const STATUS_MAP = [
        self::STATUS_NOPAY   => '未完成',
        self::STATUS_PAID    => '已支付',
        self::STATUS_CANCEL  => '已取消',
        self::STATUS_FAIL    => '失败',
        self::STATUS_TIMEOUT => '超时',
    ];

    public const STATUS_COLOR = [
        self::STATUS_NOPAY   => 'default',
        self::STATUS_PAID    => 'success',
        self::STATUS_CANCEL  => 'danger',
        self::STATUS_FAIL    => 'warning',
        self::STATUS_TIMEOUT => 'danger',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::query()->find($id);
    }

    public static function getBySn(string $sn): ?self
    {
        return self::query()->where('sn', $sn)->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getSymbolMap(): array
    {
        return [
            WalletLog::SYMBOL_USDT => WalletLog::SYMBOL_MAP[WalletLog::SYMBOL_USDT],
            WalletLog::SYMBOL_NOX  => WalletLog::SYMBOL_MAP[WalletLog::SYMBOL_NOX],
        ];
    }

    public static function allowSymbols(): array
    {
        return [
            WalletLog::SYMBOL_USDT,
            WalletLog::SYMBOL_NOX,
        ];
    }

    public static function getTotalRechargeAmount(int $user_id, string $symbol): string
    {
        return self::query()
            ->where('user_id', $user_id)
            ->where('symbol', $symbol)
            ->where('status', self::STATUS_PAID)
            ->sum('amount');
    }
}
