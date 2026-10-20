<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `pay_orders` (
//     `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//     `user_id`          int(10)       UNSIGNED NOT NULL COMMENT '用户id',
//     `sn`               varchar(100)  NOT NULL          COMMENT '支付单号',
//     `order_id`         int(10)       UNSIGNED NOT NULL COMMENT '订单id',
//     `type`             tinyint(3)    UNSIGNED NOT NULL COMMENT '类型',
//     `amount`           decimal(16,6) UNSIGNED NOT NULL COMMENT '总金额',
//     `balance`          decimal(16,6) UNSIGNED NOT NULL DEFAULT '0.000000' COMMENT '余额支付',
//     `decimals`         tinyint(3)    UNSIGNED NOT NULL COMMENT '金额小数位',
//     `status`           tinyint(3)    UNSIGNED NOT NULL COMMENT '支付状态 0=待支付 1=已支付 2=支付取消 3=支付失败 4=支付超时',
//     `pay_total`        decimal(16,6) UNSIGNED DEFAULT NULL COMMENT '支付金额',
//     `symbol`           varchar(100)  DEFAULT NULL COMMENT '资产类型',
//     `hash`             varchar(100)  DEFAULT NULL COMMENT '交易哈希',
//     `pay_contract`     varchar(100)  DEFAULT NULL COMMENT '支付合约',
//     `pay_addr`         varchar(100)  DEFAULT NULL COMMENT '付款地址',
//     `token_addr`       varchar(100)  DEFAULT NULL COMMENT '代币地址',
//     `dest_addr`        varchar(100)  DEFAULT NULL COMMENT '收款地址',
//     `fail_reason`      text COMMENT '失败原因',
//     `try_num`          int(10) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
//     `last_time`  datetime DEFAULT NULL COMMENT '超时时间',
//     `pay_at`     datetime DEFAULT NULL COMMENT '成功时间',
//     `fail_at`    datetime DEFAULT NULL COMMENT '失败或超时时间',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`sn`),
//     UNIQUE KEY (`hash`),
//     UNIQUE KEY `order_type` (`order_id`,`type`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付单';

/**
 * @property int $id
 * @property int $user_id
 * @property string $sn
 * @property string $hash
 * @property int $order_id
 * @property int $type
 * @property string $amount
 * @property string $balance
 * @property string $pay_total
 * @property int $decimals
 * @property string $symbol
 * @property int $status
 * @property string $pay_contract
 * @property string $pay_addr
 * @property string $token_addr
 * @property string $dest_addr
 * @property string|null $fail_reason
 * @property int $try_num
 * @property \Illuminate\Support\Carbon|null $last_time
 * @property \Illuminate\Support\Carbon|null $pay_at
 * @property \Illuminate\Support\Carbon|null $fail_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PayOrder extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'pay_orders';

    protected $guarded = [];

    protected $casts = [
        'amount'    => 'decimal:6',
        'balance'   => 'decimal:6',
        'pay_total' => 'decimal:6',
    ];

    const TYPE_RECHARGE = 1; // 充值订单

    const TYPE_ALPHA_BUY = 2; // 资产包申购

    const TYPE_NODE_BUY = 3; // 节点申购

    public const TYPE_MAP = [
        self::TYPE_RECHARGE  => '充值订单',
        self::TYPE_ALPHA_BUY => '资产包申购',
        self::TYPE_NODE_BUY  => '节点申购',
    ];

    public const STATUS_WAIT = 0; // 待支付

    public const STATUS_PAID = 1; // 已支付

    public const STATUS_CANCEL = 2; // 支付取消

    public const STATUS_FAIL = 3; // 支付失败

    public const STATUS_TIMEOUT = 4; // 支付超时

    public const STATUS_MAP = [
        self::STATUS_WAIT    => '待支付',
        self::STATUS_PAID    => '已支付',
        self::STATUS_CANCEL  => '支付取消',
        self::STATUS_FAIL    => '支付失败',
        self::STATUS_TIMEOUT => '支付超时',
    ];

    public const STATUS_COLOR = [
        self::STATUS_WAIT    => 'default',
        self::STATUS_PAID    => 'success',
        self::STATUS_CANCEL  => 'danger',
        self::STATUS_FAIL    => 'warning',
        self::STATUS_TIMEOUT => 'danger',
    ];

    public static function getBySn(string $sn): ?self
    {
        return self::query()->where('sn', $sn)->first();
    }

    public static function checkHashExist(string $tx_hash): bool
    {
        return self::query()->where('hash', $tx_hash)->exists();
    }

    public function getSn()
    {
        return $this->sn;
    }

    public function getPayAmountForCoin(): string
    {
        return bcmul($this->getPayAmount(), pow(10, $this->decimals));
    }

    public function getPayAmount(): string
    {
        return bcsub($this->amount, $this->balance, 6);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
