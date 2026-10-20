<?php

namespace App\Admin\Controllers;

use App\Models\Fee;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class FeesController extends AdminController
{
    protected $title = '手续费';

    protected function grid()
    {
        return Grid::make(new Fee(), function (Grid $grid) {
            $grid->model()->orderBy('date', 'desc');

            // $grid->column('id')->sortable();
            $grid->column('date', '日期')->sortable();
            $grid->column('amount', '金额')->number();

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
                $filter->between('date', '日期')->date()->width(2);
            });
        });
    }
}
