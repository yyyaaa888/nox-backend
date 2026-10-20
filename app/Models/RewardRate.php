<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `reward_rates` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `type` varchar(10) NOT NULL COMMENT 'invite/team',
//     `rank` tinyint(3) unsigned NOT NULL COMMENT 'V等级',
//     `has_node` tinyint(3) unsigned NOT NULL COMMENT '0=基础 1=持节点',
//     `layer` tinyint(3) unsigned NOT NULL COMMENT '推荐层',
//     `rate` decimal(10,2) unsigned NOT NULL COMMENT '奖励率(%)',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `reward_rates_type_rank_has_node_layer_unique` (`type`,`rank`,`has_node`,`layer`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property string $type
 * @property int $rank
 * @property int $has_node
 * @property int $layer
 * @property string $rate
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RewardRate extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'reward_rates';

    protected $guarded = [];

    protected $casts = [
        'rank'     => 'integer',
        'has_node' => 'integer',
        'layer'    => 'integer',
        'rate'     => 'decimal:2',
    ];

    public const TYPE_INVITE = 'invite'; // 推广

    public const TYPE_TEAM = 'team';   // 团队

    public const TYPE_MAP = [
        self::TYPE_INVITE => '推广收益',
        self::TYPE_TEAM   => '团队收益',
    ];
}
