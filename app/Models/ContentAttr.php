<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `content_attrs` (
//     `id`         int(10) unsigned  NOT NULL AUTO_INCREMENT,
//     `content_id` int(10) unsigned  NOT NULL COMMENT '内容id',
//     `lang`       int(10) unsigned  NOT NULL COMMENT '语言',
//     `title`      varchar(255)  DEFAULT NULL COMMENT '标题',
//     `content`    text     NULL DEFAULT NULL COMMENT '内容',
//     `created_at`      datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`      datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`content_id`, `lang`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='内容属性表';

/**
 * @property int $id
 * @property int $content_id
 * @property int $lang
 * @property string|null $content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ContentAttr newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentAttr newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ContentAttr query()
 */
class ContentAttr extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'content_attrs';

    protected $guarded = [];

    public static function getOne(int $content_id, int $lang): ?self
    {
        return self::query()->where('content_id', $content_id)->where('lang', $lang)->first();
    }

    /**
     * @param  int  $article_id
     * @return \Illuminate\Database\Eloquent\Collection|self[]
     */
    public static function getList(int $content_id)
    {
        return self::query()->where('content_id', $content_id)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAttrsMap(int $content_id)
    {
        return self::query()->where('content_id', $content_id)->get()->pluck(null, 'lang');
    }

    public static function store(int $content_id, int $lang, array $data): self
    {
        return self::query()->updateOrCreate(['content_id' => $content_id, 'lang' => $lang], $data);
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
