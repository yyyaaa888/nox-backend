<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `alpha_rewards` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `order_id` int(10) unsigned NOT NULL COMMENT '订单id',
//     `order_am` decimal(20,6) unsigned NOT NULL COMMENT '订单金额',
//     `rate` decimal(10,2) unsigned NOT NULL COMMENT '月产率(%)',
//     `reward_am` decimal(20,6) unsigned NOT NULL COMMENT '本次产出额',
//     `quota_used_am` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '计入额度槽金额',
//     `frozen_am` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '本次冻结金额',
//     `settle_date` date NOT NULL COMMENT '结算日',
//     `created_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`) USING BTREE,
//     UNIQUE KEY `alpha_rewards_order_id_settle_date_unique` (`order_id`,`settle_date`) USING BTREE,
//     KEY `alpha_rewards_user_id_index` (`user_id`) USING BTREE
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

class AlphaReward extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_rewards';

    protected $guarded = [];

    // 追加式流水表：只有 created_at，无 updated_at
    public const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(AlphaOrder::class, 'order_id');
    }
}
