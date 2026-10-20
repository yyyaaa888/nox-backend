<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `node_fee_allocations` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `sett_date` date NOT NULL COMMENT '结算日',
//     `total_fee` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '当日手续费总额',
//     `node_fee_am` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '进节点池金额',
//     `node_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活跃节点份数',
//     `per_share` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '每份分红额',
//     `project_am` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '项目收入',
//     `remain` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '未分配余量',
//     `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1=进行中 2=已结束 3=失败',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`) USING BTREE,
//     UNIQUE KEY `node_fee_allocations_sett_date_unique` (`sett_date`) USING BTREE
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

class NodeFeeAllocation extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_fee_allocations';

    protected $guarded = [];

    protected $casts = [
        'total_fee'   => 'decimal:6',
        'node_fee_am' => 'decimal:6',
        'per_share'   => 'decimal:6',
        'project_am'  => 'decimal:6',
        'remain'      => 'decimal:6',
    ];

    public const STATUS_RUNNING = 1; // 进行中

    public const STATUS_DONE = 2; // 已结束

    public const STATUS_FAIL = 3; // 失败

    public const STATUS_MAP = [
        self::STATUS_RUNNING => '进行中',
        self::STATUS_DONE    => '已结束',
        self::STATUS_FAIL    => '失败',
    ];

    public const STATUS_COLOR = [
        self::STATUS_RUNNING => 'info',
        self::STATUS_DONE    => 'success',
        self::STATUS_FAIL    => 'danger',
    ];
}
