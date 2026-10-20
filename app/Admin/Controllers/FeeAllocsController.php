<?php

namespace App\Admin\Controllers;

use App\Models\FeeAlloc;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property string $total_am
 * @property string $total_rem
 */
class FeeAllocsController extends AdminController
{
    protected $title = '手续费分配';

    protected function grid()
    {
        return Grid::make(new FeeAlloc(), function (Grid $grid) {
            $grid->model()->orderBy('sett_date', 'desc');

            // $grid->column('id')->sortable();
            $grid->column('sett_date', '日期')->sortable();
            $grid->column('total_am', '总金额')->number();
            $grid->column('node2_cc', '节点人数');
            $grid->column('real_am', '已分配')->display(function () {
                return removeTrailingZeros(bcsub($this->total_am, $this->total_rem, 6));
            });
            $grid->column('total_rem', '剩余金额')->number();
            $grid->column('status', '状态')->using(FeeAlloc::STATUS_MAP)->dot(FeeAlloc::STATUS_COLOR);

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
                $filter->equal('status', '状态')->select(FeeAlloc::STATUS_MAP)->width(2);
            });
        });
    }
}
