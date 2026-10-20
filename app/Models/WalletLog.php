<?php

namespace App\Models;

use App\Exceptions\LogicException;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `asset_logs` (
//     `id`      int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `change_type`  tinyint(0) unsigned NOT NULL COMMENT '变更类型 1=入 2=冻结 3=出 4=回撤',
//     `type`    tinyint(3) unsigned NOT NULL COMMENT '变动类型',
//     `symbol`  varchar(32) NOT NULL COMMENT '币种名称',
//     `amount`        decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '变动金额',
//     `before_amount` decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '变动前',
//     `after_amount`  decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '变动后',
//     `before_freeze` decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '变动前(冻结金额)',
//     `after_freeze`  decimal(20,6) NOT NULL DEFAULT '0.000000' COMMENT '变动后(冻结金额)',
//     `created_at`    datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`    datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY `user_id` (`user_id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账户变动表';

/**
 * @property int $id
 * @property int $user_id
 * @property int $change_type
 * @property int $type
 * @property string $symbol
 * @property string $amount
 * @property string $before_amount
 * @property string $after_amount
 * @property string $before_freeze
 * @property string $after_freeze
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|WalletLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WalletLog query()
 */
class WalletLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'asset_logs';

    protected $guarded = [];

    public const SYMBOL_USDT = 'USDT'; // USDT
    public const SYMBOL_NOX  = 'NOX';  // NOXO

    public const SYMBOL_MAP = [
        self::SYMBOL_USDT => 'USDT',
        self::SYMBOL_NOX  => 'NOXO',
    ];

    // 变更类型 1=入 2=出 3=冻结 4=回撤 5=快速出
    public const CHANGE_TYPE_IN       = 1;
    public const CHANGE_TYPE_OUT      = 2;
    public const CHANGE_TYPE_FREEZE   = 3;
    public const CHANGE_TYPE_BACK     = 4;
    public const CHANGE_TYPE_FAST_OUT = 5;

    public const CHANGE_TYPE_MAP = [
        self::CHANGE_TYPE_IN       => '入账',
        self::CHANGE_TYPE_FAST_OUT => '出帐',
        self::CHANGE_TYPE_FREEZE   => '冻结',
        self::CHANGE_TYPE_OUT      => '扣除冻结',
        self::CHANGE_TYPE_BACK     => '返还冻结',
    ];

    public const CHANGE_TYPE_COLOR = [
        self::CHANGE_TYPE_IN       => 'success',
        self::CHANGE_TYPE_FAST_OUT => 'danger',
        self::CHANGE_TYPE_FREEZE   => 'default',
        self::CHANGE_TYPE_OUT      => 'danger',
        self::CHANGE_TYPE_BACK     => 'info',
    ];

    // 充值
    public const TYPE_RECHARGE_IN     = 10; // 充值
    public const TYPE_RECHARGE_FREEZE = 11; // 扣除(冻结)
    public const TYPE_RECHARGE_OUT    = 12; // 扣除

    // 提现
    public const TYPE_WITHDRAW_IN     = 20; // 提现(发放)
    public const TYPE_WITHDRAW_FREEZE = 21; // 提现(冻结)
    public const TYPE_WITHDRAW_OUT    = 22; // 提现(扣除)
    public const TYPE_WITHDRAW_BACK   = 23; // 提现(撤回冻结)

    // 分红
    public const TYPE_FEE_FH = 120; // 手续费分红

    // NOX Alpha 资产包
    public const TYPE_ALPHA_REWARD_IN = 130; // 资产包每日产出
    public const TYPE_ALPHA_EXIT      = 131; // 资产包首月退出退款
    public const TYPE_ALPHA_UNFREEZE  = 132; // 额度槽冻结收益解冻

    // NOX Alpha 节点
    public const TYPE_NODE_FEE_IN      = 140; // 节点池分红入账
    public const TYPE_NODE_FEE_PROJECT = 141; // 节点池项目收入

    // NOX Alpha 推广/团队/平级
    public const TYPE_INVITE_REWARD     = 150; // 推广收益
    public const TYPE_TEAM_REWARD       = 151; // 团队收益
    public const TYPE_SAME_LEVEL_REWARD = 152; // 平级奖

    public const TYPE_MAP = [
        // 充值
        self::TYPE_RECHARGE_IN     => '充值',
        self::TYPE_RECHARGE_FREEZE => '扣除(冻结)',
        self::TYPE_RECHARGE_OUT    => '扣除',
        // 提现
        self::TYPE_WITHDRAW_IN     => '提现(发放)',
        self::TYPE_WITHDRAW_FREEZE => '提现(冻结)',
        self::TYPE_WITHDRAW_OUT    => '提现(扣除)',
        self::TYPE_WITHDRAW_BACK   => '提现(撤回冻结)',
        // 分红
        self::TYPE_FEE_FH => '手续费分红',
        // NOX Alpha 资产包
        self::TYPE_ALPHA_REWARD_IN => '资产包每日产出',
        self::TYPE_ALPHA_EXIT      => '资产包首月退出退款',
        self::TYPE_ALPHA_UNFREEZE  => '冻结收益解冻',
        // NOX Alpha 节点
        self::TYPE_NODE_FEE_IN      => '节点池分红',
        self::TYPE_NODE_FEE_PROJECT => '节点池项目收入',
        // NOX Alpha 推广/团队/平级
        self::TYPE_INVITE_REWARD     => '推广收益',
        self::TYPE_TEAM_REWARD       => '团队收益',
        self::TYPE_SAME_LEVEL_REWARD => '平级奖',
    ];

    // 用户显示
    public static function getTypeMapUser()
    {
        return [
            // 充值
            self::TYPE_RECHARGE_IN  => __('充值'),
            self::TYPE_RECHARGE_OUT => __('扣款'),
            // 提现
            self::TYPE_WITHDRAW_OUT => __('提现'),
            // 分红
            self::TYPE_FEE_FH => __('手续费分红'),
            // NOX Alpha 资产包
            self::TYPE_ALPHA_REWARD_IN => __('资产包每日产出'),
            self::TYPE_ALPHA_EXIT      => __('资产包首月退出退款'),
            self::TYPE_ALPHA_UNFREEZE  => __('冻结收益解冻'),
            // NOX Alpha 节点
            self::TYPE_NODE_FEE_IN      => __('节点池分红'),
            self::TYPE_NODE_FEE_PROJECT => __('节点池项目收入'),
            // NOX Alpha 推广/团队/平级
            self::TYPE_INVITE_REWARD     => __('推广收益'),
            self::TYPE_TEAM_REWARD       => __('团队收益'),
            self::TYPE_SAME_LEVEL_REWARD => __('平级奖'),
        ];
    }

    // 用户筛选
    public static function getTypeMapUserFilter(string $symbol)
    {
        if ($symbol == self::SYMBOL_USDT) {
            return [
                // 充值
                self::TYPE_RECHARGE_IN => __('充值'),
                // 提现
                self::TYPE_WITHDRAW_OUT => __('提现'),
                // 分红
                self::TYPE_FEE_FH => __('手续费分红'),
                // NOX Alpha 资产包
                self::TYPE_ALPHA_REWARD_IN => __('资产包每日产出'),
                self::TYPE_ALPHA_EXIT      => __('资产包首月退出退款'),
                self::TYPE_ALPHA_UNFREEZE  => __('冻结收益解冻'),
                // NOX Alpha 节点
                self::TYPE_NODE_FEE_IN      => __('节点池分红'),
                self::TYPE_NODE_FEE_PROJECT => __('节点池项目收入'),
                // NOX Alpha 推广/团队/平级
                self::TYPE_INVITE_REWARD     => __('推广收益'),
                self::TYPE_TEAM_REWARD       => __('团队收益'),
                self::TYPE_SAME_LEVEL_REWARD => __('平级奖'),
            ];
        } else { // NOX
            return [
                // 充值
                self::TYPE_RECHARGE_IN => __('充值'),
                // 提现
                self::TYPE_WITHDRAW_OUT => __('提现'),
            ];
        }
    }

    /**
     * @throws \App\Exceptions\LogicException
     */
    public static function addLog(int $user_id, string $amount, string $symbol, int $change_type, int $type)
    {
        $wallet = UserData::getInfo($user_id);
        if (! $wallet) {
            $wallet = UserData::addInfo($user_id);
        }
        if ($symbol == self::SYMBOL_USDT) {
            $available_amount = $wallet->current_usdt;
            $freeze_amount    = $wallet->freeze_usdt;
        } elseif ($symbol == self::SYMBOL_NOX) {
            $available_amount = $wallet->current_nox;
            $freeze_amount    = $wallet->freeze_nox;
        } else {
            return;
        }
        $after_amount        = $available_amount;
        $after_freeze_amount = $freeze_amount;
        if ($change_type == self::CHANGE_TYPE_IN) {
            $after_amount = bcadd($after_amount, $amount, 6);
        } elseif ($change_type == self::CHANGE_TYPE_FREEZE) {
            $after_amount        = bcsub($after_amount, $amount, 6);
            $after_freeze_amount = bcadd($after_freeze_amount, $amount, 6);
        } elseif ($change_type == self::CHANGE_TYPE_OUT) {
            $after_freeze_amount = bcsub($after_freeze_amount, $amount, 6);
        } elseif ($change_type == self::CHANGE_TYPE_FAST_OUT) {
            $after_amount = bcsub($after_amount, $amount, 6);
        } elseif ($change_type == self::CHANGE_TYPE_BACK) {
            $after_freeze_amount = bcsub($after_freeze_amount, $amount, 6);
            $after_amount        = bcadd($after_amount, $amount, 6);
        } else {
            return;
        }
        if (bccomp($after_amount, '0', 6) < 0) {
            throw new LogicException(__('余额不足'));
        }
        if (bccomp($after_freeze_amount, '0', 6) < 0) {
            throw new LogicException(__('余额不足'));
        }
        self::create([
            'user_id'       => $user_id,
            'change_type'   => $change_type,
            'symbol'        => $symbol,
            'amount'        => $amount,
            'before_amount' => $available_amount,
            'after_amount'  => $after_amount,
            'before_freeze' => $freeze_amount,
            'after_freeze'  => $after_freeze_amount,
            'type'          => $type,
        ]);
        if ($symbol == self::SYMBOL_USDT) {
            UserData::changeUsdtBalance($user_id, $amount, $change_type);
        } elseif ($symbol == self::SYMBOL_NOX) {
            UserData::changeNoxBalance($user_id, $amount, $change_type);
        } else {
            return;
        }
    }

    public static function getDayStakeReward(int $user_id, string $date): string
    {
        $start = $date.' 00:00:00';
        $end   = $date.' 23:59:59';

        return self::query()
            ->where('user_id', $user_id)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('type', [WalletLog::TYPE_ALPHA_REWARD_IN, WalletLog::TYPE_INVITE_REWARD, WalletLog::TYPE_TEAM_REWARD, WalletLog::TYPE_SAME_LEVEL_REWARD])
            ->sum('amount');
    }

    public static function getInfo(int $user_id): ?self
    {
        return self::query()->where('user_id', $user_id)->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
