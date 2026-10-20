<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// CREATE TABLE `user_perf_logs` (
//     `id`      int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `type`          tinyint(4) NOT NULL COMMENT '类型',
//     `change_type`   tinyint(4) NOT NULL COMMENT '变动类型 1=增加 2=减少',
//     `amount`        decimal(20,0) unsigned NOT NULL DEFAULT '0' COMMENT '变动金额',
//     `before_amount` decimal(20,0) unsigned NOT NULL DEFAULT '0' COMMENT '变动前',
//     `after_amount`  decimal(20,0) unsigned NOT NULL DEFAULT '0' COMMENT '变动后',
//     `remark`        varchar(255)           NOT NULL DEFAULT ''  COMMENT '备注',
//     `created_at`    datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`    datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='业绩日志';

/**
 * @property int $id
 * @property int $user_id
 * @property int $type
 * @property int $change_type
 * @property string $amount
 * @property string $before_amount
 * @property string $after_amount
 * @property string $remark
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserPerfLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'user_perf_logs';

    protected $guarded = [];

    public const INC = true; // 增加

    public const DEC = false; // 减少

    public const CHANGE_TYPE_IN = 1; // 增加

    public const CHANGE_TYPE_OUT = 2; // 减少

    public const CHANGE_TYPE_MAP = [
        self::CHANGE_TYPE_IN  => '增加',
        self::CHANGE_TYPE_OUT => '减少',
    ];

    public const CHANGE_TYPE_COLOR = [
        self::CHANGE_TYPE_IN  => 'success',
        self::CHANGE_TYPE_OUT => 'danger',
    ];

    public const TYPE_SYS = 10; // 系统变更

    public const TYPE_ALPHA_BUY = 11; // 资产包申购

    public const TYPE_ALPHA_EXIT = 12; // 资产包退出

    public const TYPE_MAP = [
        self::TYPE_SYS        => '系统变更',
        self::TYPE_ALPHA_BUY  => '资产包申购',
        self::TYPE_ALPHA_EXIT => '资产包退出',
    ];

    /**
     * 添加业绩日志
     *
     * @property int|UserData $user_id 用户id
     * @property string $amount 变动金额
     * @property int $type 变动类型
     * @property bool $is_add 是否增加
     * @property string $remark 备注
     *
     * @throws \App\Exceptions\LogicException
     */
    public static function addLog($user_id, string $amount, int $type, bool $is_add = true, string $remark = '')
    {
        if ($user_id instanceof UserData) {
            $wallet  = $user_id;
            $user_id = $wallet->user_id;
        } elseif ($user_id > 0) {
            $wallet = UserData::getInfo($user_id);
            if (! $wallet) {
                $wallet = UserData::addInfo($user_id);
            }
        } else {
            throw new \App\Exceptions\LogicException(__('用户不存在'));
        }
        $cur_amount = $wallet->perf;

        if (bccomp($amount, 0, 6) < 0) {
            $is_add = false;
            $amount = bcsub('0', $amount, 6);
        }

        if (! $is_add && bccomp($amount, $cur_amount, 6) > 0) {
            throw new \App\Exceptions\LogicException(__('业绩不足'));
        }

        $after_amount = $cur_amount;
        if ($is_add) {
            $after_amount = bcadd($after_amount, $amount, 6);

            // 增加用户业绩
            UserData::query()->where('user_id', $user_id)->update([
                'perf' => DB::raw('perf + '.$amount),
            ]);
        } else {
            $after_amount = bcsub($after_amount, $amount, 6);

            // 减少用户业绩
            $re = UserData::query()->where('user_id', $user_id)->where('perf', '>=', $amount)->update([
                'perf' => DB::raw('perf - '.$amount),
            ]);
            if (! $re) {
                throw new \App\Exceptions\LogicException(__('业绩不足'));
            }
        }

        self::create([
            'user_id'       => $user_id,
            'amount'        => $amount,
            'before_amount' => $cur_amount,
            'after_amount'  => $after_amount,
            'type'          => $type,
            'change_type'   => $is_add ? self::CHANGE_TYPE_IN : self::CHANGE_TYPE_OUT,
            'remark'        => $remark,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 获取某用户某天新增业绩
    public static function getNewPerformance(int $userId, \Carbon\Carbon $date): string
    {
        $result = self::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as inc_amount', [UserPerfLog::CHANGE_TYPE_IN])
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as dec_amount', [UserPerfLog::CHANGE_TYPE_OUT])
            ->first();
        $inc_amount = $result->inc_amount ?? '0';
        $dec_amount = $result->dec_amount ?? '0';

        return bcsub($inc_amount, $dec_amount, 6);
    }

    // 获取某团队某天新增业绩
    public static function getTeamNewPerformance(int $pid, \Carbon\Carbon $date): string
    {
        $result = self::query()
            ->whereIn('user_id', function ($query) use ($pid) {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $pid)->where('depth', '>', 0);
            })
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as inc_amount', [UserPerfLog::CHANGE_TYPE_IN])
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as dec_amount', [UserPerfLog::CHANGE_TYPE_OUT])
            ->first();
        $inc_amount = $result->inc_amount ?? '0';
        $dec_amount = $result->dec_amount ?? '0';

        return bcsub($inc_amount, $dec_amount, 6);
    }
}
