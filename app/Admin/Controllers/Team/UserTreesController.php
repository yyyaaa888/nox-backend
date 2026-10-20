<?php

namespace App\Admin\Controllers\Team;

use App\Admin\Renderable\Tree;
use App\Admin\Repositories\UserTreeV2Repository;
use Dcat\Admin\Http\Controllers\AdminController;

class UserTreesController extends AdminController
{
    public function index(\Dcat\Admin\Layout\Content $content)
    {
        return $content
            ->header('推荐关系查询')
            ->description('查询推荐关团队成员信息')
            ->body($this->userTree());
    }

    protected function userTree()
    {
        return Tree::make(new UserTreeV2Repository(), function (Tree $tree) {
            $invite_code = request('user.invite_code');
            $address     = request('user.address');
            $name        = request('user.name');
            if ($invite_code || $address || $name) {
                $tree->query(function ($query) use ($invite_code, $address, $name) {
                    if ($invite_code) {
                        $query = $query->where('invite_code', $invite_code);
                    }
                    if ($address) {
                        $query = $query->where('address', $address);
                    }
                    if ($name) {
                        $query = $query->where('name', $name);
                    }

                    return $query;
                });
            }
            $tree->actions(function (\Dcat\Admin\Tree\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableQuickEdit();
            });

            $tree->expand(false);
            $tree->nestable([
                'draggable' => false,
            ]);

            // $tree->disableRefreshButton();
            $tree->disableSaveButton();
            $tree->disableCreateButton();
            $tree->disableQuickCreateButton();

            // 自定义节点显示格式
            $tree->branch(function ($branch) {
                $node             = '无';
                $performance      = removeTrailingZeros($branch['performance']);
                $team_performance = removeTrailingZeros($branch['team_performance']);
                // $direct_performance = removeTrailingZeros($branch['direct_performance']);
                // $html = "<i class='fa fa-user'></i> {$branch['name']} ";
                $html = "{$branch['name']} ";
                $html .= "ID:{$branch['id']} ";
                $html .= "层级:{$branch['depth']} ";
                // $html .= "PID:{$branch['parent_id']} ";
                $html .= "邀请码:{$branch['invite_code']} ";
                $html .= "等级:V{$branch['level']} ";
                $html .= "节点:{$node} ";
                $html .= "个人业绩:{$performance} ";
                $html .= "团队业绩:{$team_performance} ";
                $html .= "团队人数:{$branch['team_count']} ";

                // $html .= "直推业绩:{$direct_performance}";
                // $html .= "直推人数:{$branch['direct_count']} ";
                return $html;
            });
        });
    }
}
