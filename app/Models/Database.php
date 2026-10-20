<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `databases` (
//     `type` tinyint(3) unsigned NOT NULL COMMENT '类型',
//     `data` text NULL DEFAULT NULL COMMENT '数据',
//     PRIMARY KEY (`type`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据库';

/**
 * @property int $type
 * @property mixed $data
 */
class Database extends Model
{
    protected $table = 'databases';

    protected $primaryKey = 'type';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'type' => 'integer',
        'data' => 'json:unicode',
    ];

    public const TYPE_INTRO = 1; // 介绍

    public const TYPE_DOCUMENT = 2; // 相关文档

    public const TYPE_POSTER = 3; // 相关海报

    public const TYPE_VIDEO = 4; // 相关视频

    public const TYPE_VIDEO_MEETING = 5; // 视频会议

    public const TYPE_MAP = [
        self::TYPE_INTRO         => '介绍',
        self::TYPE_DOCUMENT      => '相关文档',
        self::TYPE_POSTER        => '相关海报',
        self::TYPE_VIDEO         => '相关视频',
        self::TYPE_VIDEO_MEETING => '视频会议',
    ];
}
