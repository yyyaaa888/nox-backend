<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `article_attrs` (
//     `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `article_id` int(10) unsigned NOT NULL COMMENT '节点id',
//     `lang`       int(10) unsigned NOT NULL COMMENT '语言',
//     `title`      varchar(255) NOT NULL  COMMENT '名称',
//     `content`    text NULL DEFAULT NULL COMMENT '描述',
//     `created_at`     datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`     datetime DEFAULT NULL COMMENT '更新时间',
//     `deleted_at`     datetime DEFAULT NULL COMMENT '删除时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`article_id`, `lang`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章属性表';

/**
 * @property int $id
 * @property int $article_id
 * @property int $lang
 * @property string $title
 * @property string|null $content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ArticleAttr newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ArticleAttr newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ArticleAttr query()
 */
class ArticleAttr extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'article_attrs';

    protected $guarded = [];

    public static function getOne(int $article_id, int $lang): ?self
    {
        return self::query()->where('article_id', $article_id)->where('lang', $lang)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|self[]
     */
    public static function getList(int $article_id)
    {
        return self::query()->where('article_id', $article_id)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAttrsMap(int $article_id)
    {
        return self::query()->where('article_id', $article_id)->get()->pluck(null, 'lang');
    }

    public static function store(int $article_id, int $lang, array $data): self
    {
        return self::query()->updateOrCreate(['article_id' => $article_id, 'lang' => $lang], $data);
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
