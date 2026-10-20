<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `withdraws` (
//     `id`              int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`         int(10) unsigned NOT NULL COMMENT '用户id',
//     `sn`              varchar(255)  NOT NULL COMMENT '单号',
//     `symbol`          varchar(100)  NOT NULL COMMENT '账户类型',
//     `amount`          decimal(20,6) NOT NULL DEFAULT '0.000000'   COMMENT '金额',
//     `real_am`         decimal(20,6) NOT NULL DEFAULT '0.000000'   COMMENT '到手金额',
//     `fee`             decimal(20,6) NOT NULL DEFAULT '0.000000'   COMMENT '手续费',
//     `fee_rate`        decimal(20,2) NOT NULL DEFAULT '0.00'       COMMENT '手续费率',
//     `back_am`         decimal(20,6) NOT NULL DEFAULT '0.000000'   COMMENT '已返还金额',
//     `status`          tinyint(3)    unsigned NOT NULL DEFAULT '1' COMMENT '状态 1=待审核 2=转帐中 3=已完成 4=失败 5=已驳回',
//     `withdraw_mode`   tinyint(3)    unsigned NOT NULL DEFAULT '1' COMMENT '提现模式 1=审核提现 2=自动提现 3=合约提现',
//     `to_addr`         varchar(255)  NULL DEFAULT NULL             COMMENT '用户钱包地址',
//     `signature`         varchar(255) NULL DEFAULT NULL            COMMENT '签名',
//     `message`           varchar(255) NULL DEFAULT NULL            COMMENT '消息',
//     `withdraw_contract` varchar(255) NULL DEFAULT NULL            COMMENT '提现合约',
//     `project_signer`    varchar(255) NULL DEFAULT NULL            COMMENT '项目方',
//     `deadline`          datetime     NULL DEFAULT NULL            COMMENT '签名有效时间',
//     `try_num`         int(10)       unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
//     `is_back`         tinyint(3)    unsigned NOT NULL DEFAULT '0' COMMENT '是否已返还金额',
//     `hash`            varchar(255)  NULL DEFAULT NULL             COMMENT '交易凭据',
//     `audit_at`        datetime DEFAULT NULL COMMENT '审核时间',
//     `done_at`         datetime DEFAULT NULL COMMENT '完成时间',
//     `fail_at`         datetime DEFAULT NULL COMMENT '失败时间',
//     `back_at`         datetime DEFAULT NULL COMMENT '返还时间',
//     `created_at`      datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`      datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`sn`),
//     UNIQUE KEY (`hash`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现';

/**
 * @property int $id
 * @property int $user_id
 * @property string $sn
 * @property string $symbol
 * @property string $amount
 * @property string $real_am
 * @property string $fee
 * @property string $fee_rate
 * @property string $back_am
 * @property int $status
 * @property int $withdraw_mode
 * @property string $to_addr
 * @property string $signature
 * @property string $message
 * @property string $withdraw_contract
 * @property string $project_signer
 * @property \Illuminate\Support\Carbon $deadline
 * @property int $try_num
 * @property int $is_back
 * @property string|null $hash
 * @property string|null $audit_at
 * @property string|null $done_at
 * @property string|null $fail_at
 * @property string|null $back_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw query()
 */
class Withdraw extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'withdraws';

    protected $guarded = [];

    public const STATUS_AUDIT = 1;

    public const STATUS_PENDING = 2;

    public const STATUS_DONE = 3;

    public const STATUS_FAIL = 4;

    public const STATUS_REJECT = 5;

    public const STATUS_MAP = [
        // self::STATUS_AUDIT => '待审核',
        self::STATUS_PENDING => '进行中',
        self::STATUS_DONE    => '已完成',
        // self::STATUS_FAIL => '失败',
        self::STATUS_REJECT => '已驳回',
    ];

    public const STATUS_COLOR = [
        // self::STATUS_AUDIT => 'default',
        self::STATUS_PENDING => 'info',
        self::STATUS_DONE    => 'success',
        // self::STATUS_FAIL => 'danger',
        self::STATUS_REJECT => 'warning',
    ];

    public const AUDIT_STATUS_NO = 0;

    public const AUDIT_STATUS_WAIT = 1;

    public const AUDIT_STATUS_DONE = 2;

    public const AUDIT_STATUS_MAP = [
        self::AUDIT_STATUS_NO   => '不审核',
        self::AUDIT_STATUS_WAIT => '待审核',
        self::AUDIT_STATUS_DONE => '已审核',
    ];

    public const AUDIT_STATUS_COLOR = [
        self::AUDIT_STATUS_NO   => 'default',
        self::AUDIT_STATUS_WAIT => 'info',
        self::AUDIT_STATUS_DONE => 'success',
    ];

    public const WITHDRAW_MODE_AUDIT = 1; // 审核提现

    public const WITHDRAW_MODE_AUTO = 2; // 自动提现

    public const WITHDRAW_MODE_CONTRACT = 3; // 合约提现

    public const WITHDRAW_MODE_MAP = [
        self::WITHDRAW_MODE_AUDIT    => '审核提现',
        self::WITHDRAW_MODE_AUTO     => '自动提现',
        self::WITHDRAW_MODE_CONTRACT => '合约提现',
    ];

    public const DELAY_STATUS_NONE = 0;

    public const DELAY_STATUS_PENDING = 1;

    public const DELAY_STATUS_DONE = 2;

    public const DELAY_STATUS_MAP = [
        self::DELAY_STATUS_PENDING => '进行中',
        self::DELAY_STATUS_DONE    => '已完成',
    ];

    public const DELAY_STATUS_COLOR = [
        self::DELAY_STATUS_PENDING => 'info',
        self::DELAY_STATUS_DONE    => 'success',
    ];

    public static function getBySn(string $sn): ?self
    {
        return self::query()->where('sn', $sn)->first();
    }

    public static function getByPk(int $id): ?self
    {
        return self::query()->where('id', $id)->first();
    }

    public function getSn()
    {
        return $this->sn;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getDateAllTotalWithdrawAmount(string $symbol, string $date)
    {
        $times = [$date.' 00:00:00', $date.' 23:59:59'];

        return self::query()
            ->where('symbol', $symbol)
            ->whereBetween('created_at', $times)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DONE, self::STATUS_FAIL])
            ->sum('amount');
    }

    public static function getDateUserTotalWithdrawAmount(int $userId, string $symbol, string $date)
    {
        $times = [$date.' 00:00:00', $date.' 23:59:59'];

        return self::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->whereBetween('created_at', $times)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DONE, self::STATUS_FAIL])
            ->sum('amount');
    }

    /**
     * 用户某周累计提现（自然周，周一 00:00 起；status 排除驳回/取消）
     */
    public static function getWeekUserTotalWithdrawAmount(int $userId, string $symbol, string $weekStart)
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->where('created_at', '>=', $weekStart.' 00:00:00')
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DONE, self::STATUS_FAIL])
            ->sum('amount');
    }
}
