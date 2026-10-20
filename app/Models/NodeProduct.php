<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `node_products` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `name` varchar(255) NOT NULL COMMENT '节点名称',
//     `price` decimal(20,6) unsigned NOT NULL COMMENT '价格',
//     `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1=启用 0=停用',
//     `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property string $name
 * @property string $price
 * @property int $status
 * @property int $sort
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class NodeProduct extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_products';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:6',
    ];

    public const DEFAULT_ID = 1;

    public const STATUS_ON = 1;

    public const STATUS_OFF = 0;

    public const STATUS_MAP = [
        self::STATUS_ON  => '启用',
        self::STATUS_OFF => '停用',
    ];

    public static function getDefaultNode()
    {
        return self::query()->find(self::DEFAULT_ID);
    }

    public function orders()
    {
        return $this->hasMany(NodeOrder::class, 'product_id');
    }
}
