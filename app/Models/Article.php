<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

// CREATE TABLE `articles` (
//     `id` int(10) unsigned  NOT NULL AUTO_INCREMENT,
//     `title`      varchar(255) DEFAULT NULL COMMENT '标题',
//     `mini_img`   varchar(255) DEFAULT NULL COMMENT '列表图片',
//     `images`     json         DEFAULT NULL COMMENT '正文图片',
//     `is_show`    tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示',
//     `is_top`     tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶',
//     `order`      int(11)             NOT NULL DEFAULT '0' COMMENT '排序权重',
//     `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
//     `created_at`   datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`   datetime DEFAULT NULL COMMENT '更新时间',
//     `deleted_at`   datetime DEFAULT NULL COMMENT '删除时间',
//     PRIMARY KEY (`id`),
//     KEY (`publish_time`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章';

/**
 * @property int $id
 * @property string $title
 * @property string $mini_img
 * @property string $images
 * @property int $is_show
 * @property int $is_top
 * @property int $order
 * @property \Illuminate\Support\Carbon $publish_time
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Article query()
 * @method static \Illuminate\Database\Eloquent\Builder|Article isShow()
 * @method static \Illuminate\Database\Eloquent\Builder|Article isTop()
 */
class Article extends Model
{
    use HasDateTimeFormatter, SoftDeletes;

    protected $table = 'articles';

    protected $guarded = [];

    public const IS_SHOW_HIDE = 0;

    public const IS_SHOW_SHOW = 1;

    public const IS_SHOW_MAP = [
        self::IS_SHOW_HIDE => '隐藏',
        self::IS_SHOW_SHOW => '显示',
    ];

    public const IS_TOP_NO = 0;

    public const IS_TOP_YES = 1;

    public const IS_TOP_MAP = [
        self::IS_TOP_NO  => '否',
        self::IS_TOP_YES => '是',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::query()->find($id);
    }

    public function attrs()
    {
        return $this->hasMany(ArticleAttr::class, 'article_id', 'id');
    }

    public function cate()
    {
        return $this->belongsTo(ArticleCate::class, 'cate_id');
    }

    public function scopeIsShow($query)
    {
        return $query->where('is_show', self::IS_SHOW_SHOW);
    }

    public function scopeIsTop($query)
    {
        return $query->where('is_top', self::IS_TOP_YES);
    }

    // 状态作用域
    public function scopePublished($query)
    {
        return $query->where('is_show', self::IS_SHOW_SHOW)->where('publish_time', '<=', Carbon::now());
    }
}
