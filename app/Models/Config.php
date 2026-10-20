<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

// CREATE TABLE `configs` (
//     `key`   varchar(255)  NOT NULL     COMMENT '配置键',
//     `value` text                       COMMENT '配置值',
//     `group` varchar(255)  NOT NULL     COMMENT '配置组',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`key`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='参数设置表';


/**
 * @property string $key
 * @property string $value
 * @property string $group
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Config extends BaseModel
{
    use HasDateTimeFormatter;

    protected $table = 'configs';
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    public static function info()
    {
        return self::pluck('value', 'key')->toArray();
    }
}
