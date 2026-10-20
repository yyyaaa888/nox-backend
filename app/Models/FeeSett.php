<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `fee_setts` (
//     `id`             int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`        int(10) unsigned NOT NULL COMMENT '用户id',
//     `node_id`        int(10) unsigned NOT NULL COMMENT '节点id',
//     `type`           tinyint(3)    unsigned NOT NULL COMMENT '类型',
//     `user_count`     int(10)       unsigned NOT NULL COMMENT '用户数量',
//     `total_amount`   decimal(20,6) unsigned NOT NULL COMMENT '总金额',
//     `sett_amount`    decimal(20,6) unsigned NOT NULL COMMENT '结算金额',
//     `sett_date`      date     NOT NULL     COMMENT '结算日期',
//     `created_at`     datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`     datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`),
//     KEY (`node_id`),
//     KEY (`sett_date`),
//     KEY `user_date` (`user_id`, `sett_date`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手续费结算';

/**
 * @property int $id
 * @property int $user_id
 * @property int $node_id
 * @property int $type
 * @property int $user_count
 * @property string $total_amount
 * @property string $sett_amount
 * @property string $sett_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeeSett extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'fee_setts';

    protected $guarded = [];

    public const TYPE_NODE2 = 20; // 大节点

    public const TYPE_MAP = [
        self::TYPE_NODE2 => '大节点',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
