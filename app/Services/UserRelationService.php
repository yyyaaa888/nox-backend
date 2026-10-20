<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRelationClosure;
use Illuminate\Support\Facades\DB;

class UserRelationService
{
    /**
     * 在用户创建后设置其关系（闭包表）
     *
     * @param  int  $uid  新用户的ID
     * @param  int  $pid  直接上级的ID
     */
    public function setupUserRelations(int $uid, int $pid)
    {
        DB::transaction(function () use ($uid, $pid) {
            // 插入闭包路径
            $closures = [];

            // 自己到自己的路径（depth=0）
            $closures[] = [
                'ancestor_id'   => $uid,
                'descendant_id' => $uid,
                'depth'         => 0,
            ];

            if ($pid) {
                // 获取 pid 的所有祖先路径（depth >= 1）
                $ancestors = $this->getAllAncestors($pid);

                // 构建当前用户与所有祖先的关系路径
                foreach ($ancestors as $depth => $ancestorId) {
                    $closures[] = [
                        'ancestor_id'   => $ancestorId, // 祖先ID
                        'descendant_id' => $uid, // 当前用户ID
                        'depth'         => $depth + 1,
                    ];
                }

                // 插入直接上级关系（depth=1）
                $closures[] = [
                    'ancestor_id'   => $pid,
                    'descendant_id' => $uid,
                    'depth'         => 1,
                ];
            }

            // 批量插入闭包表
            UserRelationClosure::insert($closures);
        });
    }

    /**
     * 在用户创建后设置其关系（闭包表）
     *
     * @param  int  $uid  新用户的ID
     * @param  int  $pid  直接上级的ID
     */
    public function setupUserRelations2(int $uid, int $pid)
    {
        DB::transaction(function () use ($uid, $pid) {
            // 插入闭包路径
            $closures = [];

            // // 自己到自己的路径（depth=0）
            // $closures[] = [
            //     'ancestor_id' => $uid,
            //     'descendant_id' => $uid,
            //     'depth' => 0,
            // ];
            User::where('id', $uid)->update(['invited_uid' => $pid]);

            if ($pid) {
                // 获取 pid 的所有祖先路径（depth >= 1）
                $ancestors = $this->getAllAncestors($pid);

                // 构建当前用户与所有祖先的关系路径
                foreach ($ancestors as $depth => $ancestorId) {
                    $closures[] = [
                        'ancestor_id'   => $ancestorId, // 祖先ID
                        'descendant_id' => $uid, // 当前用户ID
                        'depth'         => $depth + 1,
                    ];
                }

                // 插入直接上级关系（depth=1）
                $closures[] = [
                    'ancestor_id'   => $pid,
                    'descendant_id' => $uid,
                    'depth'         => 1,
                ];
            }

            // 批量插入闭包表
            UserRelationClosure::insert($closures);
        });
    }

    /**
     * 修改用户上级，并更新闭包表中的所有关系路径
     *
     * @param  int  $uid  用户ID
     * @param  int  $newSuperId  新的上级ID
     * @param  int  $oldSuperId  旧的上级ID
     */
    public function updateUserSuperior(int $uid, int $newSuperId, int $oldSuperId = 0)
    {
        DB::transaction(function () use ($uid, $newSuperId) {
            // 删除当前用户的所有关系路径（保留自己指向自己的记录）
            UserRelationClosure::where('descendant_id', $uid)
                ->where('ancestor_id', '!=', $uid)
                ->delete();

            // 如果没有新的上级，只保留自引用即可
            if ($newSuperId == 0) {
                return;
            }

            // 获取新上级的所有祖先路径（直接+间接上级）
            $ancestors = $this->getAllAncestors($newSuperId);

            // 构建当前用户与所有祖先的关系路径
            $closures = [];

            // 自己到自己的路径（depth=0）
            $closures[] = [
                'ancestor_id'   => $uid,
                'descendant_id' => $uid,
                'depth'         => 0,
            ];

            // 插入直接上级关系（depth=1）
            $closures[] = [
                'ancestor_id'   => $newSuperId,
                'descendant_id' => $uid,
                'depth'         => 1,
            ];

            // 插入间接上级关系（depth >= 2）
            foreach ($ancestors as $depth => $ancestorId) {
                $closures[] = [
                    'ancestor_id'   => $ancestorId,
                    'descendant_id' => $uid,
                    'depth'         => $depth + 1,
                ];
            }

            // 批量插入新的关系路径
            UserRelationClosure::insert($closures);
        });
    }

    /**
     * 删除用户时，清理闭包表中的所有相关记录
     */
    public function deleteUserFromClosure(int $uid)
    {
        DB::transaction(function () use ($uid) {
            // 删除所有以该用户为上级（ancestor）或下级（descendant）的记录
            UserRelationClosure::where('ancestor_id', $uid)
                ->orWhere('descendant_id', $uid)
                ->delete();
        });
    }

    /**
     * 获取某个用户的全部祖先路径（返回数组，key为 depth）
     */
    private function getAllAncestors(int $userId): array
    {
        $path    = [];
        $current = $userId;
        $depth   = 1;

        while (true) {
            // 查询当前用户的一级上级
            $parent = UserRelationClosure::where('descendant_id', $current)
                ->where('depth', 1)
                ->value('ancestor_id');

            if (! $parent || in_array($parent, $path)) {
                break;
            }

            $path[$depth++] = $parent;
            $current        = $parent;
        }

        return $path;
    }
}
