<?php

namespace App\Admin\Controllers;

use App\Models\Level;
use App\Models\TeamReward;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class TeamRewardsController extends AdminController
{
    protected $title = '团队奖励';

    protected function grid()
    {
        $grid = new Grid(new TeamReward());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户地址')->address();
        $grid->column('child.address', '子用户地址')->address();
        $grid->column('order.sn', '订单号')->copyable();
        $grid->column('layer', '推荐层');
        $grid->column('level', '用户等级')->using(Level::LEVEL_MAP);
        $grid->column('child_level', '子用户等级')->using(Level::LEVEL_MAP);
        $grid->column('has_node', '是否节点')->using([0 => '基础', 1 => '持节点']);
        $grid->column('is_same_level', '平级')->bool();
        $grid->column('order_am', '订单收益')->number();
        $grid->column('reward_am', '团队奖')->number();
        $grid->column('reward_rate', '比例(%)')->number();
        $grid->column('sett_date', '结算日期')->sortable();
        $grid->column('created_at', '创建时间')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->like('user.name', '用户名称')->width(3);
            $filter->like('user.address', '用户地址')->width(3);
            $filter->like('child.address', '子用户地址')->width(3);
            $filter->equal('is_same_level', '平级')->select([0 => '否', 1 => '是'])->width(2);
            $filter->between('sett_date', '结算日期')->date()->width(4);
            $filter->between('created_at', '创建时间')->datetime()->width(4);
        });

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        $grid->disableActions(); // 禁用操作按钮

        return $grid;
    }
}
