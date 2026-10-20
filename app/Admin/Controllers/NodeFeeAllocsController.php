<?php

namespace App\Admin\Controllers;

use App\Models\NodeFeeAllocation;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class NodeFeeAllocsController extends AdminController
{
    protected $title = '节点池分红';

    protected function grid()
    {
        $grid = new Grid(new NodeFeeAllocation());
        $grid->model()->orderByDesc('sett_date');

        $grid->column('id', 'ID')->sortable();
        $grid->column('sett_date', '结算日期')->sortable();
        $grid->column('total_fee', '手续费总额')->number();
        $grid->column('node_fee_am', '进节点池')->number();
        $grid->column('node_count', '节点份数');
        $grid->column('per_share', '每份分红')->number();
        // $grid->column('project_am', '项目收入')->number();
        $grid->column('remain', '未分配')->number();
        $grid->column('status', '状态')->using(NodeFeeAllocation::STATUS_MAP)->dot(NodeFeeAllocation::STATUS_COLOR);

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
            $filter->between('sett_date', '结算日期')->date()->width(3);
            $filter->equal('status', '状态')->select(NodeFeeAllocation::STATUS_MAP)->width(2);
        });

        return $grid;
    }
}
