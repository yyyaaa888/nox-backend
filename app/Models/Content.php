<?php

namespace App\Models;

use App\Language;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// CREATE TABLE `contents` (
//     `id` int(10) unsigned  NOT NULL AUTO_INCREMENT,
//     `code`       varchar(255)  NOT NULL     COMMENT 'code',
//     `title`      varchar(255)  DEFAULT NULL COMMENT '标题',
//     `data`       text          DEFAULT NULL COMMENT '数据',
//     `created_at`      datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`      datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`code`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='内容表';

/**
 * @property int $id
 * @property string $code
 * @property string $title
 * @property string $img
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Content newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Content query()
 */
class Content extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'contents';

    protected $guarded = [];

    public const CODE_PLAT_INTRO = 'PLAT_INTRO';

    public const CODE_SHORT_TERM_GOAL = 'SHORT_TERM_GOAL';

    public const CODE_MID_TERM_GOAL = 'MID_TERM_GOAL';

    public const CODE_LONG_TERM_GOAL = 'LONG_TERM_GOAL';

    public const CODE_LUCKY_RULES = 'LUCKY_RULES';

    public const CODE_LINES = 'LINES';

    public const CODE_MAP = [
        self::CODE_PLAT_INTRO      => '平台介绍',
        self::CODE_SHORT_TERM_GOAL => '短期目标',
        self::CODE_MID_TERM_GOAL   => '中期规划',
        self::CODE_LONG_TERM_GOAL  => '长期愿景',
        self::CODE_LUCKY_RULES     => '幸运抽奖规则',
        self::CODE_LINES           => '生态发展路线图',
    ];

    public static function getByPk(int $id): ?self
    {
        return self::query()->find($id);
    }

    public static function getByCode(string $code): ?self
    {
        return self::query()->where('code', $code)->first();
    }

    public function attrs()
    {
        return $this->hasMany(ContentAttr::class, 'content_id', 'id');
    }

    public static function getDataByCode(string $code): ?array
    {
        return Cache::remember("content:{$code}", 60, function () use ($code) {
            $info = self::query()->where('code', $code)->first();
            if (! $info) {
                return null;
            }

            return $info->data ? json_decode($info->data, true) : null;
        });
    }

    public static function getInfoByCode(string $code, string $lang): ?array
    {
        return Cache::remember("content:{$code}:{$lang}", 60, function () use ($code, $lang) {
            $info = self::query()->with(['attrs' => function ($query) use ($lang) {
                $query->whereIn('lang', array_unique([$lang, Language::DEFAULT]));
            }])->where('code', $code)->first();
            if (! $info) {
                return null;
            }
            $attr = $info->attrs->where('lang', $lang)->first();
            if (! $attr && $lang != Language::DEFAULT) {
                $attr = $info->attrs->where('lang', Language::DEFAULT)->first();
            }
            /** @var ContentAttr|null $attr */
            if (! $attr) {
                return null;
            }

            return [
                'data'    => $info->data,
                'title'   => $attr?->title,
                'content' => $attr?->content,
            ];
        });
    }

    public static function getListByCode(string $code, string $lang): array
    {
        return Cache::remember("content:{$code}:{$lang}", 60, function () use ($code, $lang) {
            $list = self::query()->with(['attrs' => function ($query) use ($lang) {
                $query->whereIn('lang', array_unique([$lang, Language::DEFAULT]));
            }])->where('code', $code)->get();
            if ($list->isEmpty()) {
                return [];
            }
            $data = [];
            foreach ($list as $info) {
                $attr = $info->attrs->where('lang', $lang)->first();
                if (! $attr && $lang != Language::DEFAULT) {
                    $attr = $info->attrs->where('lang', Language::DEFAULT)->first();
                }
                /** @var ContentAttr|null $attr */
                if (! $attr) {
                    continue;
                }
                $data[] = [
                    'data'    => $info->data,
                    'title'   => $attr?->title,
                    'content' => $attr?->content,
                ];
            }

            return $data;
        });
    }

    public static function getContentByCode(string $code, string $lang): ?string
    {
        return Cache::remember("content:{$code}:{$lang}", 60, function () use ($code, $lang) {
            $info = self::query()->with(['attrs' => function ($query) use ($lang) {
                $query->whereIn('lang', array_unique([$lang, Language::DEFAULT]));
            }])->where('code', $code)->first();
            if (! $info) {
                return null;
            }
            $attr = $info->attrs->where('lang', $lang)->first();
            if (! $attr && $lang != Language::DEFAULT) {
                $attr = $info->attrs->where('lang', Language::DEFAULT)->first();
            }
            /** @var ContentAttr|null $attr */
            if (! $attr) {
                return null;
            }

            return $attr?->content;
        });
    }

    public static function getContentsByCode(string $code, string $lang): array
    {
        return Cache::remember("content:{$code}:{$lang}", 60, function () use ($code, $lang) {
            $list = self::query()->with(['attrs' => function ($query) use ($lang) {
                $query->whereIn('lang', array_unique([$lang, Language::DEFAULT]));
            }])->where('code', $code)->get();
            if ($list->isEmpty()) {
                return [];
            }
            $contents = [];
            foreach ($list as $info) {
                $attr = $info->attrs->where('lang', $lang)->first();
                if (! $attr && $lang != Language::DEFAULT) {
                    $attr = $info->attrs->where('lang', Language::DEFAULT)->first();
                }
                /** @var ContentAttr|null $attr */
                if (! $attr) {
                    continue;
                }
                $contents[] = $attr->content;
            }

            return $contents;
        });
    }
}
