<?php

namespace App\Admin\Controllers;

use App\Models\InviteReward;
use App\Models\Level;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class InviteRewardsController extends AdminController
{
    protected $title = '分享奖励';

    protected function grid()
    {
        $grid = new Grid(new InviteReward());
        $grid->model()->orderByDesc('id');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.address', '用户')->address();
        $grid->column('child.address', '子用户')->address();
        $grid->column('order.sn', '订单号')->copyable();
        $grid->column('level', '用户等级')->sortable()->using(Level::LEVEL_MAP);
        $grid->column('layer', '层级')->sortable();
        $grid->column('has_node', '是否节点')->sortable()->using([0 => '基础', 1 => '持节点']);
        $grid->column('order_am', '订单金额')->number();
        $grid->column('reward_am', '奖励金额')->number();
        $grid->column('reward_rate', '比例(%)')->number();
        $grid->column('created_at', '创建时间')->sortable();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->like('user.name', '用户名称')->width(3);
            $filter->like('user.address', '用户地址')->width(3);
            $filter->like('child.name', '子用户地址')->width(3);
            $filter->equal('order.sn', '订单号')->width(3);
            $filter->equal('level', '用户等级')->select(Level::LEVEL_MAP)->width(2);
            $filter->equal('layer', '层级')->width(2);
            $filter->equal('has_node', '是否节点')->select([0 => '基础', 1 => '持节点'])->width(2);
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
