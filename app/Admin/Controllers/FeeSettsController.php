<?php

namespace App\Admin\Controllers;

use App\Models\FeeSett;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class FeeSettsController extends AdminController
{
    protected $title = '节点手续费分红';

    protected function grid()
    {
        return Grid::make(new FeeSett(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('sett_date', '日期')->sortable();
            $grid->column('user.address', '用户地址')->address();
            // $grid->column('type', '类型')->using(FeeSett::TYPE_MAP);
            $grid->column('sett_amount', '分红')->number();
            $grid->column('total_amount', '总金额')->number();
            // $grid->column('user_count', '用户数量')->number();

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
                $filter->between('sett_date', '日期')->date()->width(3);
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                // $filter->equal('type', '类型')->select(FeeSett::TYPE_MAP)->width(2);
                // $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
