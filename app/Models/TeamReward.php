<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `team_rewards` (
//     `id`            int(10)    unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`       int(10)    unsigned NOT NULL COMMENT '用户id',
//     `child_id`      int(10)    unsigned NOT NULL COMMENT '子用户id',
//     `order_id`      int(10)    unsigned NOT NULL COMMENT '订单id',
//     `level`         tinyint(3) unsigned NOT NULL COMMENT '级别',
//     `prev_level`    tinyint(3) unsigned NOT NULL COMMENT '前一个级别',
//     `child_level`   tinyint(3) unsigned NOT NULL COMMENT '子用户级别',
//     `layer`         int(3)     unsigned NOT NULL DEFAULT '1' COMMENT '推荐层',
//     `has_node`      tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上级持节点',
//     `is_same_level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '平级奖',
//     `order_am`      decimal(20,6) unsigned NOT NULL COMMENT '订单金额',
//     `reward_am`     decimal(20,6) unsigned NOT NULL COMMENT '奖励金额',
//     `reward_rate`   decimal(10,2) unsigned NOT NULL COMMENT '收益率(%)',
//     `sett_date`  date     NOT NULL     COMMENT '结算日期',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`),
//     KEY (`child_id`),
//     KEY (`order_id`),
//     KEY (`sett_date`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队奖励';

/**
 * @property int $id
 * @property int $user_id
 * @property int $child_id
 * @property int $order_id
 * @property int $level
 * @property int $prev_level
 * @property int $child_level
 * @property int $layer
 * @property int $has_node
 * @property int $is_same_level
 * @property string $order_am
 * @property string $reward_am
 * @property string $reward_rate
 * @property string $sett_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TeamReward extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'team_rewards';

    protected $guarded = [];

    protected $casts = [
        'level'         => 'integer',
        'prev_level'    => 'integer',
        'child_level'   => 'integer',
        'layer'         => 'integer',
        'has_node'      => 'integer',
        'is_same_level' => 'integer',
        'order_am'      => 'decimal:6',
        'reward_am'     => 'decimal:6',
        'reward_rate'   => 'decimal:2',
    ];

    public const STATUS_PENDING = 0; // 待发放

    public const STATUS_DONE = 1; // 已发放

    public const STATUS_MAP = [
        self::STATUS_PENDING => '待发放',
        self::STATUS_DONE    => '已发放',
    ];

    public const STATUS_COLOR = [
        self::STATUS_PENDING => 'info',
        self::STATUS_DONE    => 'success',
    ];

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
