<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `fee_allocs` (
//     `id`             int(10)       unsigned NOT NULL AUTO_INCREMENT,
//     `total_am`       decimal(20,6) unsigned NOT NULL COMMENT '总金额',
//     `node2_am`       decimal(20,6) unsigned NOT NULL COMMENT '节点2金额',
//     `node2_rate`     decimal(6,2)  unsigned NOT NULL COMMENT '节点2比例(%)',
//     `node2_cc`       int(10)       unsigned NOT NULL DEFAULT 0          COMMENT '节点2人数',
//     `node2_rem`      decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '节点2剩余',
//     `total_rem`      decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '总共剩余',
//     `status`         tinyint(3)    unsigned NOT NULL COMMENT '状态 1=进行中 2=已结束 3=失败',
//     `sett_date`      date     NOT NULL     COMMENT '结算日期',
//     `start_at`       datetime DEFAULT NULL COMMENT '开始时间',
//     `end_at`         datetime DEFAULT NULL COMMENT '结束时间',
//     `created_at`     datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`     datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`sett_date`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手续费分配';

/**
 * @property int $id
 * @property string $total_am
 * @property string $node2_am
 * @property string $node2_rate
 * @property int $node2_cc
 * @property string $node2_rem
 * @property string $total_rem
 * @property int $status
 * @property string $sett_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeeAlloc extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'fee_allocs';

    protected $guarded = [];

    public const STATUS_PENDING = 1; // 进行中

    public const STATUS_DONE = 2; // 已结束

    public const STATUS_FAIL = 3; // 失败

    public const STATUS_MAP = [
        self::STATUS_PENDING => '进行中',
        self::STATUS_DONE    => '已结束',
        self::STATUS_FAIL    => '失败',
    ];

    public const STATUS_COLOR = [
        self::STATUS_PENDING => 'info',
        self::STATUS_DONE    => 'success',
        self::STATUS_FAIL    => 'danger',
    ];
}
