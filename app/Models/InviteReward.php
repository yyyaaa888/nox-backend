<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `invite_rewards` (
//     `id`          int(10)       unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`     int(10)       unsigned NOT NULL COMMENT '用户id',
//     `child_id`    int(10)       unsigned NOT NULL COMMENT '子用户id',
//     `order_id`    int(10)       unsigned NOT NULL COMMENT '订单id',
//     `level`       tinyint(3)    unsigned NOT NULL COMMENT '级别',
//     `layer`       int(3)        unsigned NOT NULL DEFAULT '1'        COMMENT '推荐层',
//     `has_node`    tinyint(3)    unsigned NOT NULL DEFAULT '0'        COMMENT '上级持节点',
//     `order_am`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '订单金额',
//     `reward_am`   decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '奖励金额',
//     `reward_rate` decimal(10,2) unsigned NOT NULL DEFAULT '0.00'     COMMENT '比例(%)',
//     `created_at`  datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`  datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`),
//     KEY (`child_id`),
//     KEY (`order_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分享奖励';

/**
 * @property int $id
 * @property int $user_id
 * @property int $child_id
 * @property int $order_id
 * @property int $level
 * @property int $layer
 * @property int $has_node
 * @property string $order_am
 * @property string $reward_am
 * @property string $reward_rate
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InviteReward extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'invite_rewards';

    protected $guarded = [];

    protected $casts = [
        'order_am'    => 'decimal:6',
        'reward_am'   => 'decimal:6',
        'reward_rate' => 'decimal:2',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::query()->find($id);
    }

    public static function getUserRewardForChild(int $user_id, int $child_id): string
    {
        return self::query()->where('user_id', $user_id)->where('child_id', $child_id)->sum('reward_am');
    }

    public static function getDayReward(int $user_id, string $date): string
    {
        $start = $date.' 00:00:00';
        $end   = $date.' 23:59:59';

        return self::query()->where('user_id', $user_id)->whereBetween('created_at', [$start, $end])->sum('reward_am');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function order()
    {
        return $this->belongsTo(AlphaOrder::class, 'order_id');
    }
}
