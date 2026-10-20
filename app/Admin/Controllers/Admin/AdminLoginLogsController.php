<?php

namespace App\Admin\Controllers\Admin;

use App\Admin\Models\AdminLoginLog;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class AdminLoginLogsController extends AdminController
{
    protected $title = '登录日志';

    protected function grid()
    {
        return Grid::make(new AdminLoginLog(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            // 如果是管理员，显示所有日志，否则只显示当前管理员的日志
            $user = Admin::user();
            if (! $user->isAdministrator()) {
                $grid->model()->whereIn('admin_id', [0, $user->id]);
            }

            $grid->column('id')->sortable();
            $grid->column('admin_id', '管理员ID')->sortable();
            $grid->column('admin_name', '登录账号')->sortable();
            $grid->column('login_ip', '登录IP')->sortable();
            $grid->column('login_num', '24H登录次数')->sortable();
            $grid->column('fail_num', '24H失败次数')->sortable();
            $grid->column('status', '状态')->using(AdminLoginLog::STATUS_MAP)->dot(AdminLoginLog::STATUS_COLOR);
            $grid->column('remark', '备注');

            $grid->column('created_at', '登录时间')->sortable();

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
                $filter->equal('admin_id', '管理员ID')->width(2);
                $filter->like('admin_name', '登录账号')->width(2);
                $filter->like('login_ip', '登录IP')->width(2);
                $filter->equal('status', '状态')->select(AdminLoginLog::STATUS_MAP)->width(2);
                $filter->between('created_at', '登录时间')->datetime()->width(4);
            });
        });
    }
}
