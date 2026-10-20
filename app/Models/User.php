<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// CREATE TABLE `users` (
//     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
//     `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `address` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
//     `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `email_verified_at` timestamp NULL DEFAULT NULL,
//     `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `pay_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `created_at` timestamp NULL DEFAULT NULL,
//     `updated_at` timestamp NULL DEFAULT NULL,
//     `status` tinyint(1) DEFAULT '1',
//     `invited_uid` int(10) unsigned NOT NULL DEFAULT '0',
//     `invite_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
//     `level_id` int(10) unsigned NOT NULL DEFAULT '1',
//     `virtual_level` int(10) unsigned NOT NULL DEFAULT '1',
//     `nft_id` int(10) unsigned NOT NULL DEFAULT '0',
//     `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
//     `team_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '团队人数',
//     `direct_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '直推人数',
//     `user_perf` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '个人业绩',
//     `team_perf` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '团队业绩',
//     `big_perf` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '大区业绩',
//     `small_perf` decimal(20,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '小区业绩',
//     `perf_at` datetime DEFAULT NULL COMMENT '业绩更新时间',
//     `allow_w_usdt` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '允许USDT提现',
//     `allow_w_nox` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '允许NOX提现',
//     PRIMARY KEY (`id`) USING BTREE,
//     UNIQUE KEY `users_email_unique` (`email`) USING BTREE,
//     UNIQUE KEY `users_address_unique` (`address`) USING BTREE,
//     UNIQUE INDEX(`invite_code`) USING BTREE,
//     INDEX(`invited_uid`) USING BTREE,
//     INDEX(`place_pid`) USING BTREE
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/**
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $avatar
 * @property string $pay_password
 * @property int $invited_uid
 * @property string $invite_code
 * @property int $level_id
 * @property int $virtual_level
 * @property int $nft_id
 * @property int $team_num
 * @property int $direct_num
 * @property string $user_perf
 * @property string $team_perf
 * @property string $big_perf
 * @property string $small_perf
 * @property string|null $perf_at
 * @property int $status
 * @property int $allow_w_usdt
 * @property int $allow_w_nox
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = true;

    public const PERFORMANCE_KEY = 'perf';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'avatar',
        'name',
        'address',
        'password',
        'invite_code',
        'invited_uid',
        'level_id',
        'virtual_level',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 模型的日期字段
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    const USER_STATUS_NORMAL = 1; // 正常用户

    const USER_STATUS_FORBIDDEN = 0; // 被禁用用户

    public static function getInfo(int $uid): ?self
    {
        return self::query()->find($uid);
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function userCount(int $num)
    {
        $userCount = 0;
        switch ($num) {
            case '1':
                $userCount = self::query()->count();
                break;
            case '2':
                $userCount = self::query()->where('status', 1)->count();
                break;
            case '3':
                $userCount = self::query()->where('status', 0)->count();
                break;
        }

        return $userCount;
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    // 用户下级关联
    public function children()
    {
        return $this->hasMany(User::class, 'invited_uid', 'id');
    }

    public function directTeam()
    {
        return $this->hasMany(User::class, 'invited_uid');
    }

    public function userData()
    {
        return $this->hasOne(UserData::class, 'user_id', 'id');
    }

    /**
     * 获取钱包
     *
     * @return mixed
     */
    public function userBalance($type = 'token')
    {
        return UserAsset::firstOrCreate([
            'uid'       => $this->id,
            'assets_id' => Asset::ASSETS_TOKENS[$type],
        ]);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'invited_uid', 'id');
    }

    // 获取用户的所有团队成员
    public static function getAllChilds(int $uid, int $level = 1): array
    {
        if ($level > 100) {
            return [];
        }
        $childs = self::query()->where('invited_uid', $uid)->pluck('id')->toArray();
        foreach ($childs as $child) {
            $childs = array_merge($childs, self::getAllChilds($child));
        }

        return $childs;
    }

    // 获取用户的直接成员
    public static function getSubChilds(int $uid): array
    {
        return self::query()->where('invited_uid', $uid)->pluck('id')->toArray();
    }

    // 获取所有下属（递归）
    public function getAllSubordinates()
    {
        $subordinates = collect();

        foreach ($this->children as $child) {
            $subordinates->push($child);
            $subordinates = $subordinates->merge($child->getAllSubordinates());
        }

        return $subordinates;
    }

    // 直推人数
    public static function directCount(int $uid)
    {
        return self::query()->where('invited_uid', $uid)->count();
    }

    // 团队人数
    public static function teamCount(int $uid)
    {
        return count(self::getAllChilds($uid));
    }

    // 获取团队有效人数
    public static function teamValidCount(int $userId): int
    {
        return UserData::query()->whereIn('user_id', function ($query) use ($userId) {
            $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', '>', 0);
        })->where(self::PERFORMANCE_KEY, '>', 0)->count();
    }

    // 获取直推有效人数
    public static function directValidCount(int $userId): int
    {
        return UserData::query()->whereIn('user_id', function ($query) use ($userId) {
            $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', 1);
        })->where(self::PERFORMANCE_KEY, '>', 0)->count();
    }

    // 直推列表
    public static function getDirectList(int $uid)
    {
        return self::query()->where('invited_uid', $uid)
            ->join('levels', 'users.level_id', '=', 'levels.id', 'left')
            ->join('user_datas', 'user_datas.user_id', '=', 'users.id')
            ->select('users.id', 'users.address', 'users.level_id as level_id', 'vto_levels.name as level_name', 'user_datas.'.self::PERFORMANCE_KEY.' as performance')
            ->get();
    }

    // 间推列表
    public static function getIndirectList(int $uid)
    {
        $childs = self::getSubChilds($uid);

        $subordinates = collect();
        foreach ($childs as $child) {
            $subordinates = $subordinates->merge(self::getDirectList($child));
        }

        return $subordinates;
    }

    /**
     * 获取某用户个人业绩
     */
    public static function getPersonalPerformance(int $userId): string
    {
        return UserData::query()->where('user_id', $userId)->value(self::PERFORMANCE_KEY) ?: '0.000000';
    }

    /**
     * 获取某用户的团队业绩（不包含自己）
     */
    public static function getTeamPerformance(int $userId, int $max_depth = 0): string
    {
        $totalPerformance = UserData::query()->whereIn('user_id', function ($query) use ($userId, $max_depth) {
            // 获取所有下级用户
            if ($max_depth > 0) {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->whereBetween('depth', [1, $max_depth]);
            } else {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', '>', 0);
            }
        })->sum(self::PERFORMANCE_KEY);

        return $totalPerformance;
    }

    /**
     * 获取某用户的团队业绩（包含自己）
     */
    private static function getTeamPerformance2(int $userId, int $max_depth = 0): string
    {
        $totalPerformance = UserData::query()->whereIn('user_id', function ($query) use ($userId, $max_depth) {
            // 获取所有下级用户（包含自己）
            if ($max_depth > 0) {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', '<=', $max_depth);
            } else {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId);
            }
        })->sum(self::PERFORMANCE_KEY);

        return $totalPerformance;
    }
    //
    // /**
    //  * 获取直接下属的团队业绩
    //  */
    // public static function getDirectPerformances(int $userId): array
    // {
    //     // 获取直接下级用户
    //     $direct_users = UserRelationClosure::where('ancestor_id', $userId)
    //         ->where('depth', 1)
    //         ->get();

    //     $performances = [];
    //     foreach ($direct_users as $user) {
    //         $performances[$user->descendant_id] = self::getTeamPerformance2($user->descendant_id);
    //     }

    //     return $performances;
    // }

    /**
     * 获取直接下属的总业绩
     */
    public static function getDirectPerformance(int $userId): string
    {
        $totalPerformance = UserData::query()->whereIn('user_id', function ($query) use ($userId) {
            // 获取直接下级用户
            $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', 1);
        })->sum(self::PERFORMANCE_KEY);

        return $totalPerformance;
    }

    /**
     * 直推节点人数
     */
    public static function getDirectNodeCount(int $userId): string
    {
        $totalPerformance = UserData::query()->whereIn('user_id', function ($query) use ($userId) {
            // 获取直接下级用户
            $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', 1);
        })->where('node_id', '>', 0)->count();

        return $totalPerformance;
    }

    /**
     * 团队节点人数
     */
    public static function getTeamNodeCount(int $userId, int $max_depth = 0): string
    {
        $totalPerformance = UserData::query()->whereIn('user_id', function ($query) use ($userId, $max_depth) {
            // 获取所有下级用户
            if ($max_depth > 0) {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->whereBetween('depth', [1, $max_depth]);
            } else {
                $query->select('descendant_id')->from('user_relation_closures')->where('ancestor_id', $userId)->where('depth', '>', 0);
            }
        })->where('node_id', '>', 0)->count();

        return $totalPerformance;
    }

    /**
     * 获取团队业绩
     */
    public static function getTeamPerformances(int $userId): array
    {
        // 获取直接下级用户
        $direct_users = UserRelationClosure::query()->where('ancestor_id', $userId)
            ->where('depth', 1)
            ->get();

        $totalPerformance   = '0.000000'; // 团队总业绩
        $bigAreaPerformance = '0.000000'; // 大区业绩
        foreach ($direct_users as $user) {
            $performance      = self::getTeamPerformance2($user->descendant_id);
            $totalPerformance = bcadd($totalPerformance, $performance, 6);
            if ($performance > $bigAreaPerformance) {
                $bigAreaPerformance = $performance;
            }
        }

        return [$totalPerformance, $bigAreaPerformance];
    }
}
