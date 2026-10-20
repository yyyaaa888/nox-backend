<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

// CREATE TABLE `announcements` (
//     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `title_cn` varchar(255) NOT NULL COMMENT '标题',
//     `title_en` varchar(255) NOT NULL,
//     `title_tw` varchar(255) NOT NULL,
//     `title_ko` varchar(255) NOT NULL,
//     `title_ja` varchar(255) NOT NULL,
//     `content_cn` mediumtext NOT NULL COMMENT '内容',
//     `content_en` mediumtext NOT NULL,
//     `content_tw` mediumtext NOT NULL,
//     `content_ko` mediumtext NOT NULL,
//     `content_ja` mediumtext NOT NULL,
//     `status`     tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0=未发布 1=已发布',
//     `order`      int(11) NOT NULL DEFAULT '0' COMMENT '排序权重',
//     `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
//     `created_at`   datetime NOT     NULL COMMENT '添加时间',
//     `updated_at`   datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统公告';

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $guarded = [];

    public const STATUS_UNPUBLISHED = 0;

    public const STATUS_PUBLISHED = 1;

    public const STATUS_MAP = [
        self::STATUS_UNPUBLISHED => '未发布',
        self::STATUS_PUBLISHED   => '已发布',
    ];

    public const STATUS_COLOR = [
        self::STATUS_UNPUBLISHED => 'default',
        self::STATUS_PUBLISHED   => 'success',
    ];

    // 状态作用域
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('publish_time', '<=', Carbon::now());
    }
}
