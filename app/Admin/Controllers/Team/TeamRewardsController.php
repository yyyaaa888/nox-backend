<?php

namespace App\Admin\Controllers\Team;

use App\Models\Level;
use App\Models\TeamReward;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class TeamRewardsController extends AdminController
{
    protected $title = '团队奖励';

    protected function grid()
    {
        return Grid::make(new TeamReward(['user', 'child']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            $grid->column('child.address', '子用户地址')->address();
            $grid->column('level', '用户等级')->using(Level::LEVEL_MAP);
            $grid->column('order_am', '订单收益')->number();
            $grid->column('reward_am', '奖励金额')->number();
            $grid->column('reward_rate', '比例(%)')->number();

            $grid->column('created_at')->sortable();

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            $grid->disableActions(); // 禁用操作按钮

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                $filter->equal('level', '用户等级')->select(Level::LEVEL_MAP)->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
