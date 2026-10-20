<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

// CREATE TABLE `partners` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
//     `name`        varchar(255)     DEFAULT NULL COMMENT '机构名称',
//     `logo_url`    varchar(512)     DEFAULT NULL COMMENT '机构图标URL',
//     `is_show`     tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
//     `order`       int(11)    NOT NULL DEFAULT 0 COMMENT '排序权重',
//     `created_at` datetime NOT NULL     COMMENT '添加时间',
//     `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
//     `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
//     PRIMARY KEY (`id`),
//     KEY idx_name (`name`), -- 按名称查询
//     KEY idx_active_order (`is_show`, `order`), -- 查询显示状态和排序（最常用查询）
//     KEY idx_sort_order (`order`) -- 按排序查询
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='合作机构';

/**
 * @property int $id
 * @property int $is_show
 * @property int $order
 * @property string $name
 * @property string $logo_url
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Partner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner query()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner isShow()
 */
class Partner extends Model implements Sortable
{
    use HasDateTimeFormatter, SoftDeletes, SortableTrait;

    protected $table = 'partners';

    protected $guarded = [];

    public $sortable = [
        'order_column_name'  => 'order',
        'sort_when_creating' => true,
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
}
