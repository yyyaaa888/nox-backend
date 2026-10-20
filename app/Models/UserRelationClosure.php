<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// CREATE TABLE `user_relation_closures` (
//     `ancestor_id`   int(11) NOT NULL COMMENT '祖先id',
//     `descendant_id` int(11) NOT NULL COMMENT '用户id',
//     `depth`         int(11) NOT NULL COMMENT '层级',
//     PRIMARY KEY (`ancestor_id`, `descendant_id`),
//     KEY `descendant` (`descendant_id`, `depth`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户关系';

/**
 * @property int $ancestor_id
 * @property int $descendant_id
 * @property int $depth
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserRelationClosure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRelationClosure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserRelationClosure query()
 */
class UserRelationClosure extends Model
{
    protected $table = 'user_relation_closures';

    protected $guarded = [];

    public $timestamps = false; // 关闭自动维护时间戳

    public const MAX_DEPTH = 5;

    public function ancestor()
    {
        return $this->belongsTo(User::class, 'ancestor_id');
    }

    public function descendant()
    {
        return $this->belongsTo(User::class, 'descendant_id');
    }

    // 查询某个用户的所有上级（含层级）
    public static function getAncestorsWithDepth(int $user_id)
    {
        return self::query()->where('descendant_id', $user_id)->get();
    }

    // 查询某个用户的所有上级（不含自己）
    public static function getAncestors(int $user_id, int $max_depth = 0)
    {
        if ($max_depth == 0) {
            return self::query()->where('descendant_id', $user_id)->where('depth', '>', 0)->get();
        }

        return self::query()->where('descendant_id', $user_id)->whereBetween('depth', [1, $max_depth])->get();
    }

    // 查询某个用户的指定层级以内的上级列表(不含自己)
    public static function getAncestorsWithDepthLimit(int $user_id, int $depth_limit)
    {
        if ($depth_limit == 1) {
            return self::query()->where('descendant_id', $user_id)->where('depth', 1)->get();
        }

        return self::query()->where('descendant_id', $user_id)->whereBetween('depth', [1, $depth_limit])->orderBy('depth')->get();
    }

    // 查询某个用户的所有下级
    public static function getDescendants(int $user_id, int $max_depth = 0)
    {
        if ($max_depth == 0) {
            return self::query()->where('ancestor_id', $user_id)->where('depth', '>', 0)->get();
        }

        return self::query()->where('ancestor_id', $user_id)->whereBetween('depth', [1, $max_depth])->get();
    }

    // 查询某个用户下的1级下级
    public static function getDirectDescendants(int $user_id)
    {
        return self::query()->where('ancestor_id', $user_id)->where('depth', 1)->get();
    }

    // 查询某个用户下的2级下级
    public static function getSecondDescendants(int $user_id)
    {
        return self::query()->where('ancestor_id', $user_id)->where('depth', 2)->get();
    }

    // 查询某个用户的指定层级下级
    public static function getLevelDescendants(int $user_id, int $level)
    {
        return self::query()->where('ancestor_id', $user_id)->where('depth', $level)->get();
    }

    // 获取团队人数(不含自己)
    public static function getTeamCount(int $user_id, int $max_depth = 0): int
    {
        if ($max_depth == 0) {
            return self::query()->where('ancestor_id', $user_id)->where('depth', '>', 0)->count();
        }

        return self::query()->where('ancestor_id', $user_id)->whereBetween('depth', [1, $max_depth])->count();
    }

    // 获取直属人数
    public static function getDirectCount(int $user_id): int
    {
        return self::query()->where('ancestor_id', $user_id)->where('depth', 1)->count();
    }

    // 获取指定层级人数
    public static function getLevelCount(int $user_id, int $level): int
    {
        return self::query()->where('ancestor_id', $user_id)->where('depth', $level)->count();
    }

    /**
     * 获取团队业绩数据
     */
    public static function getTeamPerformances(int $uid, int $max_depth = 0): array
    {
        // 个人业绩
        $userPerf = User::getPersonalPerformance($uid);

        // 获取团队人数
        $team_num = self::getTeamCount($uid, $max_depth);

        // 获取直接下级用户
        $direct_users = self::query()->where('ancestor_id', $uid)
            ->where('depth', 1)
            ->get();

        $teamPerformance     = '0.000000'; // 团队总业绩
        $bigPerformance      = '0.000000'; // 大区业绩
        $directPerformance   = '0.000000'; // 直推业绩
        $direct_num          = 0;
        $eff_num             = 0; // 有效直推
        $direct_performances = [];
        foreach ($direct_users as $user) {
            $direct_num++;
            if ($max_depth != 1) {
                $direct_max_depth = $max_depth > 0 ? $max_depth - 1 : 0;
                $performance      = User::getTeamPerformance($user->descendant_id, $direct_max_depth); // 团队业绩
            } else {
                $performance = '0.000000';
            }
            // 个人业绩
            $personalPerformance = User::getPersonalPerformance($user->descendant_id);
            // 个人业绩大于0，则有效直推人数+1
            if (bccomp($personalPerformance, '0.000000', 6) > 0) {
                $eff_num += 1;
                $directPerformance = bcadd($directPerformance, $personalPerformance, 6);
            }
            $performance                               = bcadd($performance, $personalPerformance, 6);
            $direct_performances[$user->descendant_id] = $performance;
            $teamPerformance                           = bcadd($teamPerformance, $performance, 6);
            if ($performance > $bigPerformance) {
                $bigPerformance = $performance;
            }
        }

        // 取第二大区、第三大区的业绩
        $direct_performances = array_values($direct_performances);
        sort($direct_performances);
        // $second_total = $direct_performances[count($direct_performances) - 2] ?? '0.000000';
        // $third_total = $direct_performances[count($direct_performances) - 3] ?? '0.000000';
        // 取前三大的业绩
        $depts = array_slice($direct_performances, -3);

        $smallPerformance = bcsub($teamPerformance, $bigPerformance, 6);
        $allPerformance   = bcadd($userPerf, $teamPerformance, 6);

        return [
            'user_perf'    => $userPerf,               // 个人业绩
            'num'          => $team_num,               // 团队人数
            'direct'       => $direct_num,             // 直推人数
            'eff'          => $eff_num,                // 有效直推
            'all'          => $allPerformance,         // 总业绩
            'total'        => $teamPerformance,        // 团队业绩
            'direct_total' => $directPerformance,      // 直推业绩
            'small_total'  => $smallPerformance,       // 小区业绩
            'big_total'    => $bigPerformance,         // 大区业绩
            // 'total_2nd'     => $second_total,           // 第二大区业绩
            // 'total_3rd'     => $third_total,            // 第三大区业绩
            'depts' => $depts,                  // 前三大区业绩
        ];
    }
}
