<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `level_histories` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id`     int(10) unsigned NOT NULL COMMENT '用户id',
//     `old_level`   int(10) unsigned NOT NULL COMMENT '原等级',
//     `new_level`   int(10) unsigned NOT NULL COMMENT '新等级',
//     `remark`      text    NULL DEFAULT NULL COMMENT '备注',
//     `created_at`      datetime     NOT NULL COMMENT '添加时间',
//     `updated_at`      datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户等级历史';

/**
 * @property int $id
 * @property int $user_id
 * @property int $old_level
 * @property int $new_level
 * @property string|null $remark
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LevelHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LevelHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LevelHistory query()
 */
class LevelHistory extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'level_histories';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function oldLevel()
    {
        return $this->belongsTo(Level::class, 'old_level');
    }

    public function newLevel()
    {
        return $this->belongsTo(Level::class, 'new_level');
    }
}
