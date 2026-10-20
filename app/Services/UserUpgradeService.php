<?php

namespace App\Services;

use App\Models\Level;
use App\Models\LevelHistory;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserRelationClosure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserUpgradeService
{
    private $log = null;

    public function __construct(bool $write_log = false)
    {
        if ($write_log) {
            $this->log = Log::channel('user-upgrade');
        }
    }

    public function run(int $uid)
    {
        // 获取所有上级
        $parents = UserRelationClosure::query()->where('descendant_id', $uid)->orderBy('depth', 'asc')->get()->toArray();
        $pids    = [];
        foreach ($parents as $parent) {
            $pids[] = $parent['ancestor_id'];
            $this->check($parent['ancestor_id']);
        }

        return $pids;
    }

    public function check(int $uid)
    {
        /** @var User $user */
        $user = User::query()->find($uid);
        if (! $user) {
            return;
        }

        // 获取团队业绩数据
        $team_data = UserRelationClosure::getTeamPerformances($uid, UserRelationClosure::MAX_DEPTH);
        $this->checkUserLevel($user, $team_data);
    }

    private function checkUserLevel(User $user, array $team_data)
    {
        $uid       = $user->id;
        $old_level = $user->level_id ?: 0;
        $v_level   = $user->virtual_level ?: 0;

        // 级别
        $levels = $this->loadLevelData();
        if (empty($levels)) {
            $this->addLog('获取升级配置失败', 'ERROR');

            return;
        }

        // 持节点(node_id>0)会员等级下限为 V3：持有节点即享 V3 身份起步（配置存在后才查，避免空配置时多余查询）
        $nodeHolder = (int) UserData::query()->where('user_id', $uid)->value('node_id') > 0;

        // 需要升到的级别
        $new_level = 0;
        foreach ($levels as $level) {
            $level_id = $level['id'];
            if ($level_id < 5) { // 级别1-4考查个人业绩和团队业绩，要求其中至少一个达标
                if ($team_data['user_perf'] < $level['user_perf'] && $team_data['total'] < $level['team_perf']) { // 个人业绩和团队业绩都不达标
                    continue;
                }
            } else { // 级别5只考查团队业绩
                if ($team_data['total'] < $level['team_perf']) { // 团队业绩不达标
                    continue;
                }
            }
            if ($team_data['eff'] < $level['direct_num']) { // 有效直推人数不达标
                continue;
            }
            if ($team_data['num'] < $level['team_num']) { // 团队人数不达标
                continue;
            }
            $new_level = $level_id;
            break;
        }

        // 实际等级不能低于虚拟等级
        $v_flag = false;
        if ($new_level < $v_level) {
            $new_level = $v_level;
            $v_flag = true;
        }

        $perfLevel       = $new_level;
        $nodeFloorActive = false;
        if ($nodeHolder && $perfLevel < 3) {
            $new_level       = 3; // 业绩不足时由持有节点保底到 V3
            $nodeFloorActive = true;
        }

        $flag       = false;
        $is_upgrade = false;
        // 改变级别
        if ($new_level > $old_level) { // 升级
            $flag       = true;
            $is_upgrade = true;
        } elseif ($new_level < $old_level) { // 降级
            $flag       = true;
            $is_upgrade = false;
        }
        if ($flag) {
            $verb = $is_upgrade ? '升级到' : '降级到';
            $note = $nodeFloorActive ? '（持有节点保底V3）' : '';
            if ($v_flag) {
                $note .= '（虚拟等级V'.$v_level.'）';
            }
            $this->addLog(
                '用户 %s 从 V%s %s V%s, 个人业绩 %s, 团队业绩 %s, 有效直推人数 %s, 团队人数 %s%s',
                'INFO',
                $uid,
                $old_level,
                $verb,
                $new_level,
                $team_data['user_perf'],
                $team_data['total'],
                $team_data['eff'],
                $team_data['num'],
                $note
            );
        }

        $up = [];
        if ($flag) {
            $up['level_id'] = $new_level;
        }
        $up['team_num']   = $team_data['num'];          // 团队人数
        $up['direct_num'] = $team_data['direct'];       // 直推人数
        $up['user_perf']  = $team_data['user_perf'];    // 个人业绩
        $up['team_perf']  = $team_data['total'];        // 团队业绩
        $up['big_perf']   = $team_data['big_total'];    // 大区业绩
        $up['small_perf'] = $team_data['small_total'];  // 小区业绩
        $up['perf_at']    = Carbon::now();

        DB::beginTransaction();
        try {
            User::query()->where('id', $uid)->update($up);
            if ($flag) {
                // 添加升级记录
                $team_data['user_perf'] = removeTrailingZeros($team_data['user_perf']);
                $team_data['total']     = removeTrailingZeros($team_data['total']);
                $remark                 = "个人业绩: {$team_data['user_perf']}, 团队业绩: {$team_data['total']}, 有效直推人数: {$team_data['eff']}, 团队人数: {$team_data['num']}";
                if ($nodeFloorActive) {
                    $remark .= '；持有节点保底V3';
                }
                if ($v_flag) {
                    $remark .= '；虚拟等级V'.$v_level;
                }
                $log            = new LevelHistory();
                $log->user_id   = $uid;
                $log->new_level = $new_level;
                $log->old_level = $old_level;
                $log->remark    = $remark;
                $log->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addLog('保存等级信息失败，用户 %s, 错误信息 %s', 'ERROR', $uid, $e->getMessage());
        }
    }

    private function loadLevelData()
    {
        $levels = Level::query()->orderBy('id', 'desc')->get()->toArray();
        if (empty($levels)) {
            $this->addLog('等级信息为空', 'ERROR');

            return null;
        }

        return array_column($levels, null, 'id');
    }

    protected function addLog(string $format, string $INFO = 'INFO', ...$args)
    {
        if ($this->log) {
            switch ($INFO) {
                case 'ERROR':
                    $this->log->error(sprintf($format, ...$args));
                    break;
                case 'INFO':
                    $this->log->info(sprintf($format, ...$args));
                    break;
            }
        }
    }
}
