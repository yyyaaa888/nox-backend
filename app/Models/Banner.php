<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

// CREATE TABLE `banners` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
//     `pos_id`      int(10) unsigned NOT NULL     COMMENT '位置',
//     `img`         varchar(1024)    DEFAULT NULL COMMENT '图片',
//     `href`        varchar(1024)    DEFAULT NULL COMMENT '链接',
//     `remark`      varchar(255)     DEFAULT NULL COMMENT '备注',
//     `is_show`     tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
//     `order`       int(11)    NOT NULL DEFAULT 0 COMMENT '排序权重',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
//     PRIMARY KEY (`id`),
//     KEY (`is_show`, `order`),
//     KEY (`order`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='banner';

/**
 * @property int $id
 * @property string $img
 * @property string $href
 * @property string $remark
 * @property int $is_show
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner isShow()
 */
class Banner extends Model implements Sortable
{
    use HasDateTimeFormatter, SoftDeletes, SortableTrait;

    protected $table = 'banners';

    protected $guarded = [];

    public $sortable = [
        'order_column_name'  => 'order',
        'sort_when_creating' => true,
    ];

    public const POS_INDEX = 1; // 首页顶部广告图

    public const POS_MAP = [
        self::POS_INDEX => '首页顶部广告图',
    ];

    public const IS_SHOW_HIDE = 0;

    public const IS_SHOW_SHOW = 1;

    public const IS_SHOW_MAP = [
        self::IS_SHOW_HIDE => '隐藏',
        self::IS_SHOW_SHOW => '显示',
    ];

    public const IS_SHOW_COLOR = [
        self::IS_SHOW_HIDE => 'default',
        self::IS_SHOW_SHOW => 'success',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::query()->find($id);
    }

    public function scopeIsShow($query)
    {
        return $query->where('is_show', self::IS_SHOW_SHOW);
    }

    /**
     * 获取指定位置的banner列表
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function getListByPos(int $pos)
    {
        return self::query()->where('pos_id', $pos)->isShow()->orderBy('order', 'asc')->get();
    }

    public static function getOneByPos(int $pos): ?self
    {
        return self::query()->where('pos_id', $pos)->isShow()->orderBy('order', 'asc')->first();
    }
}
