<?php

namespace App\Admin\Controllers;

use App\Models\NodeFeeLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class NodeFeeLogsController extends AdminController
{
    protected $title = '节点手续费分红明细';

    protected function grid()
    {
        return Grid::make(new NodeFeeLog(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('sett_date', '结算日期')->sortable();
            $grid->column('user.address', '用户')->address();
            $grid->column('am', '分红金额')->number();
            $grid->column('node_num', '用户节点数');
            $grid->column('total_am', '总金额')->number();
            $grid->column('total_num', '节点总数');
            $grid->column('per_share', '每份分红')->number();

            // $grid->column('created_at')->sortable();

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
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                // $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
