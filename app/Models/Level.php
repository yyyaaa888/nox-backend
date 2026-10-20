<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `levels` (
//     `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `code`       varchar(30)      NOT NULL COMMENT '编号',
//     `user_perf`  int(10) unsigned NOT NULL COMMENT '个人业绩',
//     `team_perf`  int(10) unsigned NOT NULL COMMENT '团队业绩',
//     `direct_num` int(10) unsigned NOT NULL COMMENT '直推人数',
//     `team_num`   int(10) unsigned NOT NULL COMMENT '团队人数',
//     `weekly_limit_usdt` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '周USDT提现上限',
//     `weekly_limit_nox`  decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '周NOX提现上限',
//     `created_at` datetime NOT NULL COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY `code_idx` (`code`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户等级';

/**
 * @property int $id
 * @property string $code
 * @property int $user_perf
 * @property int $team_perf
 * @property int $direct_num
 * @property int $team_num
 * @property string $weekly_limit_usdt
 * @property string $weekly_limit_nox
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Level extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'levels';

    protected $guarded = [];

    protected $casts = [
        'weekly_limit_usdt' => 'decimal:6',
        'weekly_limit_nox'  => 'decimal:6',
    ];

    public const LEVEL_MAP = [
        1 => 'V1',
        2 => 'V2',
        3 => 'V3',
        4 => 'V4',
        5 => 'V5',
    ];

    public const FULL_LEVEL_MAP = [
        // 0 => 'V0',
        1 => 'V1',
        2 => 'V2',
        3 => 'V3',
        4 => 'V4',
        5 => 'V5',
    ];

    public const MAX_LEVEL = 5;

    public static function getInfo(int $id): ?self
    {
        return self::find($id);
    }
}
