<?php

namespace App\Models;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `sys_recharges` (
//     `id`              int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`         int(10) unsigned NOT NULL COMMENT '用户id',
//     `admin_id`        int(10) unsigned NOT NULL COMMENT '管理员id',
//     `asset`           varchar(255) NOT NULL COMMENT '资产类型',
//     `type`            tinyint(3) unsigned NOT NULL COMMENT '类型 1=充值 2=扣款',
//     `amount`          decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '金额',
//     `remark`          varchar(255) DEFAULT NULL COMMENT '备注',
//     `created_at`      datetime NOT NULL COMMENT '添加时间',
//     `updated_at`      datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值扣款';

/**
 * @property int $id
 * @property int $user_id
 * @property int $admin_id
 * @property string $asset
 * @property int $type
 * @property string $amount
 * @property string $remark
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Recharge extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'sys_recharges';

    protected $guarded = [];

    public const TYPE_RECHARGE = 1; // 充值
    public const TYPE_OUT      = 2; // 扣款

    public const TYPE_MAP = [
        self::TYPE_RECHARGE => '充值',
        self::TYPE_OUT      => '扣款',
    ];

    public const ASSET_USDT = 'USDT'; // USDT
    public const ASSET_NOX  = 'NOX'; // NOXO

    public const ASSET_MAP = [
        self::ASSET_USDT => 'USDT',
        self::ASSET_NOX  => 'NOXO',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Administrator::class, 'admin_id');
    }
}
