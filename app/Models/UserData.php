<?php

namespace App\Models;

use App\Exceptions\LogicException;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// CREATE TABLE `user_datas` (
//     `id`       int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`  int(10) unsigned NOT NULL COMMENT '用户id',
//     `node_id`  int(10) unsigned NOT NULL DEFAULT 0 COMMENT '节点id',
//     `node_num` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '节点数量',
//     `total_stake`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '累计质押',
//     `curr_stake`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '当前质押',
//     `total_power`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '累计算力',
//     `curr_power`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '当前算力',
//     -- 业绩
//     `perf`           decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '业绩',
//     -- USDT
//     `total_usdt`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '累计USDT',
//     `current_usdt`   decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '当前USDT',
//     `freeze_usdt`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '冻结USDT',
//     -- NOX
//     `total_nox`       decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '累计NOX',
//     `current_nox`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '当前NOX',
//     `freeze_nox`      decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '冻结NOX',
//     -- 其他
//     `node_reward`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '节点收益',
//     `stake_reward`   decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '质押收益',
//     `invite_reward`  decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '邀请奖励',
//     `team_reward`    decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '团队奖励',
//     `fee_fh`         decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '手续费分红',
//     `node_at`        datetime DEFAULT NULL COMMENT '节点持有时间',
//     `created_at`     datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`     datetime DEFAULT NULL COMMENT '更新时间',
//     `current_slot`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '额度槽',
//     `alpha_quota_used` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '额度槽已用(C)',
//     `alpha_frozen`     decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '冻结收益',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `user_id` (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户数据';

/**
 * @property int $id
 * @property int $user_id
 * @property int $node_id
 * @property int $node_num
 * @property string $total_stake
 * @property string $curr_stake
 * @property string $total_power
 * @property string $curr_power
 * @property string $perf
 * @property string $total_usdt
 * @property string $current_usdt
 * @property string $freeze_usdt
 * @property string $total_nox
 * @property string $current_nox
 * @property string $freeze_nox
 * @property string $node_reward
 * @property string $stake_reward
 * @property string $invite_reward
 * @property string $team_reward
 * @property string $fee_fh
 * @property string $current_slot
 * @property string $alpha_quota_used
 * @property string $alpha_frozen
 * @property \Illuminate\Support\Carbon|null $node_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserData query()
 */
class UserData extends Model
{
    use HasDateTimeFormatter, HasFactory;

    protected $table = 'user_datas';

    protected $guarded = [];

    protected $casts = [
        'node_at'          => 'datetime',
        'total_stake'      => 'decimal:6',
        'curr_stake'       => 'decimal:6',
        'current_slot'     => 'decimal:6',
        'total_power'      => 'decimal:6',
        'curr_power'       => 'decimal:6',
        'perf'             => 'decimal:6',
        'total_usdt'       => 'decimal:6',
        'current_usdt'     => 'decimal:6',
        'freeze_usdt'      => 'decimal:6',
        'total_nox'        => 'decimal:6',
        'current_nox'      => 'decimal:6',
        'freeze_nox'       => 'decimal:6',
        'node_reward'      => 'decimal:6',
        'stake_reward'     => 'decimal:6',
        'invite_reward'    => 'decimal:6',
        'team_reward'      => 'decimal:6',
        'fee_fh'           => 'decimal:6',
        'alpha_quota_used' => 'decimal:6',
        'alpha_frozen'     => 'decimal:6',
    ];

    // 变更用户USDT余额
    public static function changeUsdtBalance(int $user_id, string $num, int $change_type)
    {
        $re = false;
        switch ($change_type) {
            case WalletLog::CHANGE_TYPE_IN:
                $re = self::query()->where('user_id', $user_id)->update([
                    'total_usdt'   => DB::raw('total_usdt + '.$num),
                    'current_usdt' => DB::raw('current_usdt + '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_FREEZE:
                $re = self::query()->where('user_id', $user_id)->where('current_usdt', '>=', $num)->update([
                    'current_usdt' => DB::raw('current_usdt - '.$num),
                    'freeze_usdt'  => DB::raw('freeze_usdt + '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_OUT:
                $re = self::query()->where('user_id', $user_id)->where('freeze_usdt', '>=', $num)->update([
                    'freeze_usdt' => DB::raw('freeze_usdt - '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_FAST_OUT:
                $re = self::query()->where('user_id', $user_id)->where('current_usdt', '>=', $num)->update([
                    'current_usdt' => DB::raw('current_usdt - '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_BACK:
                $re = self::query()->where('user_id', $user_id)->where('freeze_usdt', '>=', $num)->update([
                    'freeze_usdt'  => DB::raw('freeze_usdt - '.$num),
                    'current_usdt' => DB::raw('current_usdt + '.$num),
                ]);
                break;
        }
        if (! $re) {
            throw new LogicException(__('余额变更失败'));
        }
    }

    // 变更用户NOX余额
    public static function changeNoxBalance(int $user_id, string $num, int $change_type)
    {
        $re = false;
        switch ($change_type) {
            case WalletLog::CHANGE_TYPE_IN:
                $re = self::query()->where('user_id', $user_id)->update([
                    'total_nox'   => DB::raw('total_nox + '.$num),
                    'current_nox' => DB::raw('current_nox + '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_FREEZE:
                $re = self::query()->where('user_id', $user_id)->where('current_nox', '>=', $num)->update([
                    'current_nox' => DB::raw('current_nox - '.$num),
                    'freeze_nox'  => DB::raw('freeze_nox + '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_OUT:
                $re = self::query()->where('user_id', $user_id)->where('freeze_nox', '>=', $num)->update([
                    'freeze_nox' => DB::raw('freeze_nox - '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_FAST_OUT:
                $re = self::query()->where('user_id', $user_id)->where('current_nox', '>=', $num)->update([
                    'current_nox' => DB::raw('current_nox - '.$num),
                ]);
                break;
            case WalletLog::CHANGE_TYPE_BACK:
                $re = self::query()->where('user_id', $user_id)->where('freeze_nox', '>=', $num)->update([
                    'freeze_nox'  => DB::raw('freeze_nox - '.$num),
                    'current_nox' => DB::raw('current_nox + '.$num),
                ]);
                break;
        }
        if (! $re) {
            throw new LogicException(__('余额变更失败'));
        }
    }

    /**
     * @return self
     */
    public static function addInfo(int $user_id)
    {
        return self::create([
            'user_id' => $user_id,
        ]);
    }

    public static function getInfo(int $user_id): ?self
    {
        return self::query()->where('user_id', $user_id)->first();
    }

    // 获取全网算力
    public static function getTotalPower(): string
    {
        return self::query()->sum('curr_power');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
