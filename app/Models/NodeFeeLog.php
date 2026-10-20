<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `node_fee_logs` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `alloc_id` int(10) unsigned NOT NULL COMMENT '分配记录id',
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `am` decimal(20,6) unsigned NOT NULL COMMENT '该用户分红金额',
//     `node_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '该用户节点数量',
//     `total_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '本次全局节点总数',
//     `total_am` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '分红总金额(池金额)',
//     `per_share` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '每份分红额',
//     `sett_date` date NOT NULL COMMENT '结算日期',
//     `created_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `node_fee_logs_alloc_id_user_id_unique` (`alloc_id`,`user_id`),
//     UNIQUE KEY `node_fee_logs_sett_date_user_id_unique` (`sett_date`,`user_id`),
//     KEY `node_fee_logs_user_id_index` (`user_id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property int $alloc_id
 * @property int $user_id
 * @property string $am
 * @property int $node_num
 * @property int $total_num
 * @property string $total_am
 * @property string $per_share
 * @property string $sett_date
 * @property \Illuminate\Support\Carbon $created_at
 */
class NodeFeeLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_fee_logs';

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected $casts = [
        'node_num'  => 'integer',
        'total_num' => 'integer',
        'am'        => 'decimal:6',
        'total_am'  => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function allocation()
    {
        return $this->belongsTo(NodeFeeAllocation::class, 'alloc_id');
    }
}
