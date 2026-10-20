<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `alpha_orders` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `product_id` int(10) unsigned NOT NULL COMMENT '产品id',
//     `sn` varchar(20)  NOT NULL COMMENT '单号',
//     `amount` decimal(20,6) unsigned NOT NULL COMMENT '本金',
//     `power_am` decimal(20,6) unsigned NOT NULL COMMENT '算力值',
//     `token_price` decimal(20,8) unsigned NOT NULL DEFAULT '0' COMMENT '代币汇率',
//     `reward_rate` decimal(10,2) unsigned NOT NULL COMMENT '月产率快照(%)',
//     `quota_reward` decimal(20,6) unsigned NOT NULL COMMENT '静态收益封顶=2.5*本金',
//     `rem_reward` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '剩余可产出收益',
//     `rel_reward` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '累计已产出',
//     `last_reward` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '最近产出',
//     `last_day` date DEFAULT NULL COMMENT '最近产出日期',
//     `trial_end` datetime DEFAULT NULL COMMENT '首月试用截止',
//     `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0=待支付 1=进行中 2=已完成 3=已退出 4=取消',
//     `pay_at` datetime DEFAULT NULL COMMENT '支付时间',
//     `done_at` datetime DEFAULT NULL COMMENT '完成时间',
//     `exit_at` datetime DEFAULT NULL COMMENT '退出时间',
//     `fail_at` datetime DEFAULT NULL COMMENT '取消/失败时间',
//     `created_at` datetime DEFAULT NULL,
//     `updated_at` datetime DEFAULT NULL,
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `alpha_orders_sn_unique` (`sn`),
//     KEY `alpha_orders_user_id_index` (`user_id`),
//     KEY `alpha_orders_status_index` (`status`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $sn
 * @property string $amount
 * @property string $power_am
 * @property string $token_price
 * @property string $reward_rate
 * @property string $quota_reward
 * @property string $rem_reward
 * @property string $rel_reward
 * @property string $last_reward
 * @property string $last_day
 * @property \Illuminate\Support\Carbon $trial_end
 * @property int $status
 * @property string|null $pay_at
 * @property string|null $done_at
 * @property string|null $exit_at
 * @property string|null $fail_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property User $user
 * @property AlphaProduct $product
 */
class AlphaOrder extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'alpha_orders';

    protected $guarded = [];

    protected $casts = [
        'reward_rate'  => 'decimal:2',
        'amount'       => 'decimal:6',
        'power_am'     => 'decimal:6',
        'quota_reward' => 'decimal:6',
        'rem_reward'   => 'decimal:6',
        'rel_reward'   => 'decimal:6',
        'last_reward'  => 'decimal:6',
        'trial_end'    => 'datetime',
        'pay_at'       => 'datetime',
        'done_at'      => 'datetime',
        'exit_at'      => 'datetime',
        'fail_at'      => 'datetime',
    ];

    public const STATUS_WAIT = 0; // 待支付

    public const STATUS_PAID = 1; // 进行中

    public const STATUS_DONE = 2; // 已完成(2.5x到顶)

    public const STATUS_EXITED = 3; // 已退出(首月退80%)

    public const STATUS_CANCEL = 4; // 取消

    public const STATUS_MAP = [
        self::STATUS_WAIT   => '待支付',
        self::STATUS_PAID   => '进行中',
        self::STATUS_DONE   => '已完成',
        self::STATUS_EXITED => '已退出',
        self::STATUS_CANCEL => '取消',
    ];

    public const STATUS_COLOR = [
        self::STATUS_WAIT   => 'default',
        self::STATUS_PAID   => 'info',
        self::STATUS_DONE   => 'success',
        self::STATUS_EXITED => 'warning',
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
        return $this->belongsTo(AlphaProduct::class, 'product_id');
    }
}
