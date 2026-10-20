<?php

namespace App\Admin\Repositories;

use App\Models\User;
use App\Models\UserRelationClosure;
use Dcat\Admin\Contracts\TreeRepository;
use Illuminate\Support\Collection;

class UserTreeV2Repository implements TreeRepository
{
    protected $model;

    protected $fields = ['id', 'name', 'remark', 'invited_uid', 'invite_code', 'level_id'];

    public function __construct()
    {
        $this->model = new User();
    }

    /**
     * @return mixed
     */
    public function getKeyName()
    {
        return 'id';
    }

    /**
     * 获取主键字段名（默认 id）
     */
    public function getPrimaryKeyColumn(): string
    {
        return 'id';
    }

    /**
     * 获取父级字段名（闭包表不适用此字段）
     */
    public function getParentColumn(): string
    {
        return 'parent_id'; // 仅用于普通嵌套集模型，闭包表可忽略
    }

    /**
     * 获取标题字段名（如 name）
     */
    public function getTitleColumn(): string
    {
        return 'name';
    }

    /**
     * 获取排序字段名（如 order）
     */
    public function getOrderColumn(): string
    {
        return 'order';
    }

    /**
     * 保存节点排序
     */
    public function saveOrder($tree = [], $parentId = 0)
    {
        // 可选实现，如果不需要拖拽排序可以留空
    }

    /**
     * 查询构建器
     */
    public function withQuery($queryCallback): self
    {
        if ($queryCallback instanceof \Closure) {
            $this->model = $queryCallback($this->model);
        }

        return $this;
    }

    /**
     * 转换为树结构
     */
    public function toTree()
    {
        // 检查模型是否设置查询条件
        if (! $this->model->getQuery()->wheres) {
            return null;
        }

        /** @var User[]|Collection $root_users */
        $root_users = $this->model->select($this->fields)->get();

        // 获取团队所有成员，包括自己
        $children   = UserRelationClosure::query()->whereIn('ancestor_id', $root_users->pluck('id'))->get();
        $member_ids = $children->pluck('descendant_id')->unique()->toArray();

        // 获取所有成员的关系链
        $refs = UserRelationClosure::query()->whereIn('ancestor_id', $member_ids)->get()->toArray();

        // 获取所有成员数据
        $users = User::query()
            ->with('userData:user_id,node_id,'.User::PERFORMANCE_KEY)
            ->whereIn('id', $member_ids)
            ->select($this->fields)
            ->get()
            ->pluck(null, 'id')
            ->toArray();

        // 个人业绩
        $performances = [];
        // 团队业绩
        $team_performances = [];
        $team_counts       = [];
        // 直推业绩
        $direct_performances = [];
        $direct_counts       = [];
        // 直推人ids
        $direct_pids = [];
        foreach ($refs as $ref) {
            $_pid                = $ref['ancestor_id'];
            $_cid                = $ref['descendant_id'];
            $_depth              = $ref['depth'];
            $performances[$_cid] = $_performance = bcadd($users[$_cid]['user_data'][User::PERFORMANCE_KEY] ?? '0', '0', 6);
            if ($_depth > 0) { // 团队业绩
                $team_counts[$_pid]       = isset($team_counts[$_pid]) ? $team_counts[$_pid] + 1 : 1;
                $team_performances[$_pid] = bcadd($team_performances[$_pid] ?? '0', $_performance, 6);
            }
            if ($_depth == 1) { // 直推
                $direct_counts[$_pid]       = isset($direct_counts[$_pid]) ? $direct_counts[$_pid] + 1 : 1;
                $direct_pids[$_cid]         = $_pid;
                $direct_performances[$_pid] = bcadd($direct_performances[$_pid] ?? '0', $_performance, 6);
            }
        }
        // 清理变量 防止内存溢出
        unset($refs, $ref);

        $data = new Collection();
        foreach ($children as $child) {
            $uid  = $child->descendant_id;
            $user = $users[$child->descendant_id] ?? null;
            if (! $user) {
                continue;
            }
            $parent_id = $user['invited_uid'] ?: 0;
            $node_id   = $user['user_data']['node_id'] ?: 0;
            // $pid = $direct_pids[$uid] ?? 0;
            // if ($parent_id != $pid && $root_users->where('id', $uid)->count() == 0) {
            //     return null;
            // }
            $data->push(new Collection([
                'id'                 => $uid,
                'parent_id'          => $parent_id,
                'pid'                => $direct_pids[$uid] ?? 0,
                'depth'              => $child->depth,
                'name'               => $user['remark'] ? $user['name'].'('.$user['remark'].')' : $user['name'],
                'level'              => $user['level_id'] ?: 0, // 级别
                'node_id'            => $node_id, // 节点
                'invite_code'        => $user['invite_code'],
                'performance'        => $performances[$uid] ?? '0', // 个人业绩
                'team_performance'   => $team_performances[$uid] ?? '0', // 团队业绩
                'direct_performance' => $direct_performances[$uid] ?? '0', // 直推业绩
                'team_count'         => $team_counts[$uid] ?? 0, // 团队人数
                'direct_count'       => $direct_counts[$uid] ?? 0, // 直推人数
            ]));
        }

        $branch = new Collection();
        foreach ($root_users as $user) {
            $element = $data->where('id', $user->id)->first();
            if ($element) {
                $children            = self::buildTree($data, $user->id);
                $element['children'] = $children->isEmpty() ? null : $children;
                $branch->push($element);
            }
        }

        return $branch;
    }

    private static function buildTree(Collection $elements, $parent_id = 0)
    {
        $branch = new Collection();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parent_id) {
                $children            = self::buildTree($elements, $element['id']);
                $element['children'] = $children->isEmpty() ? null : $children;
                $branch->push($element);
            }
        }

        return $branch;
    }
}
