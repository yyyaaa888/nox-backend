<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `article_cates` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `name`       varchar(255)         NOT NULL COMMENT '名称',
//     `color`      tinyint(10) unsigned NOT NULL DEFAULT 0 COMMENT '颜色',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章类型';

/**
 * @property int $id
 * @property string $name
 * @property int $color
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ArticleCate extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'article_cates';

    protected $guarded = [];

    public const COLOR_1 = 1;

    public const COLOR_2 = 2;

    public const COLOR_MAP = [
        self::COLOR_1 => '#94FFBB',
        self::COLOR_2 => '#FFA1A1',
    ];

    public const COLOR_NAME_MAP = [
        self::COLOR_1 => '绿色背景',
        self::COLOR_2 => '红色背景',
    ];

    public function attrs()
    {
        return $this->hasMany(ArticleCateAttr::class, 'cate_id', 'id');
    }
}
