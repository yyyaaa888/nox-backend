<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `alpha_products` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `name` varchar(255) NOT NULL COMMENT '产品名称',
//     `tier` varchar(20) NOT NULL COMMENT '档位 basic/high',
//     `min_amount` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '最小申购额',
//     `max_amount` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '最大申购额',
//     `per_power` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '每单位算力值',
//     `min_rate`  decimal(10,2) unsigned NOT NULL COMMENT '最低月产率(%)',
//      `max_rate` decimal(10,2) unsigned NOT NULL COMMENT '最高月产率(%)',
//     `base_rate` decimal(10,2) unsigned NOT NULL COMMENT '基础月产率(%)',
//     `node_rate` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '持节点加速月产率(%)',
//     `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1=启用 0=停用',
//     `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property string $name
 * @property string $tier
 * @property string $min_amount
 * @property string $max_amount
 * @property string $per_power
 * @property string $min_rate
 * @property string $max_rate
 * @property string $base_rate
 * @property string $node_rate
 * @property int $status
 * @property int $sort
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AlphaProduct extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_products';

    protected $guarded = [];

    public const TIER_BASIC = 'basic';

    public const TIER_HIGH = 'high';

    public const TIER_MAP = [
        self::TIER_BASIC => '基础算力方案',
        self::TIER_HIGH  => '高额算力方案',
    ];

    public const STATUS_ON = 1;

    public const STATUS_OFF = 0;

    public const STATUS_MAP = [
        self::STATUS_ON  => '启用',
        self::STATUS_OFF => '停用',
    ];

    public function orders()
    {
        return $this->hasMany(AlphaOrder::class, 'product_id');
    }
}
