<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `article_cate_attrs` (
//     `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `cate_id`    int(10) unsigned NOT NULL COMMENT '类型id',
//     `lang`       int(10) unsigned NOT NULL COMMENT '语言',
//     `name`       varchar(255) NOT NULL COMMENT '名称',
//     `created_at`     datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`     datetime DEFAULT NULL COMMENT '更新时间',
//     `deleted_at`     datetime DEFAULT NULL COMMENT '删除时间',
//     PRIMARY KEY (`id`),
//     UNIQUE KEY (`cate_id`, `lang`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章类型属性表';

/**
 * @property int $id
 * @property int $cate_id
 * @property int $lang
 * @property string $name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ArticleCateAttr extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'article_cate_attrs';

    protected $guarded = [];

    public static function getOne(int $cate_id, int $lang): ?self
    {
        return self::query()->where('cate_id', $cate_id)->where('lang', $lang)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|self[]
     */
    public static function getList(int $cate_id)
    {
        return self::query()->where('cate_id', $cate_id)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAttrsMap(int $cate_id)
    {
        return self::query()->where('cate_id', $cate_id)->get()->pluck(null, 'lang');
    }

    public static function store(int $cate_id, int $lang, array $data): self
    {
        return self::query()->updateOrCreate(['cate_id' => $cate_id, 'lang' => $lang], $data);
    }

    public function cate()
    {
        return $this->belongsTo(ArticleCate::class, 'cate_id');
    }
}
