<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `node_orders` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `product_id` int(10) unsigned NOT NULL COMMENT '产品id',
//     `sn` varchar(20) NOT NULL COMMENT '单号',
//     `num` int(10) unsigned NOT NULL COMMENT '数量',
//     `price` decimal(20,6) unsigned NOT NULL COMMENT '单价',
//     `amount` decimal(20,6) unsigned NOT NULL COMMENT '本金',
//     `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0=待支付 1=持有中 2=取消',
//     `pay_at` datetime DEFAULT NULL,
//     `fail_at` datetime DEFAULT NULL,
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `node_orders_sn_unique` (`sn`),
//     KEY `node_orders_user_id_index` (`user_id`),
//     KEY `node_orders_status_index` (`status`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $sn
 * @property int $num
 * @property string $price
 * @property string $amount
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $pay_at
 * @property \Illuminate\Support\Carbon|null $fail_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class NodeOrder extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'node_orders';

    protected $guarded = [];

    protected $casts = [
        'amount'  => 'decimal:6',
        'pay_at'  => 'datetime',
        'fail_at' => 'datetime',
    ];

    public const STATUS_WAIT = 0; // 待支付

    public const STATUS_PAID = 1; // 持有中

    public const STATUS_CANCEL = 2; // 取消

    public const STATUS_MAP = [
        self::STATUS_WAIT   => '待支付',
        self::STATUS_PAID   => '持有中',
        self::STATUS_CANCEL => '取消',
    ];

    public const STATUS_COLOR = [
        self::STATUS_WAIT   => 'default',
        self::STATUS_PAID   => 'success',
        self::STATUS_CANCEL => 'danger',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::find($id);
    }

    public static function getBySn(string $sn): ?self
    {
        return self::query()->where('sn', $sn)->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(NodeProduct::class, 'product_id');
    }
}
