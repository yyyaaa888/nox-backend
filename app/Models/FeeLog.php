<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// CREATE TABLE `fee_logs` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`       int(10)       unsigned NOT NULL COMMENT '用户id',
//     `type`          tinyint(3)    unsigned NOT NULL COMMENT '事件类型',
//     `amount`        decimal(20,6) unsigned NOT NULL COMMENT '金额',
//     `remark`        text                            COMMENT '备注',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手续费日志';

/**
 * @property int $id
 * @property int $user_id
 * @property int $type
 * @property string $amount
 * @property string|null $remark
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FeeLog extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'fee_logs';

    protected $guarded = [];

    public const TYPE_WITHDRAW = 1; // 提现

    public const TYPE_MAP = [
        self::TYPE_WITHDRAW => '提现',
    ];

    public static function addLog(int $user_id, string $amount, int $type, ?string $remark = null): self
    {
        $now  = Carbon::now();
        $date = $now->toDateString();
        // Fee::query()->updateOrCreate([
        //     'date' => $date,
        // ], [
        //     'amount' => DB::raw('amount + ' . $amount),
        // ]);
        $re = Fee::query()->where('date', $date)->update([
            'amount'     => DB::raw('amount + '.$amount),
            'updated_at' => $now,
        ]);
        if (! $re) {
            Fee::query()->create([
                'date'       => $date,
                'amount'     => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return self::query()->create([
            'user_id' => $user_id,
            'type'    => $type,
            'amount'  => $amount,
            'remark'  => $remark,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
