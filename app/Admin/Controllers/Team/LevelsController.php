<?php

namespace App\Admin\Controllers\Team;

use App\Models\Level;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class LevelsController extends AdminController
{
    protected $title = '等级设置';

    protected function grid()
    {
        return Grid::make(new Level(), function (Grid $grid) {
            $grid->column('id', '级别')->sortable();
            // $grid->column('code', '编号');
            $grid->column('user_perf', '个人业绩')->display(function ($value) {
                return number_format($value);
            });
            $grid->column('team_perf', '团队业绩')->display(function ($value) {
                return number_format($value);
            });
            $grid->column('direct_num', '直推人数')->display(function ($value) {
                return number_format($value);
            })->help('有效直推人数');
            $grid->column('team_num', '团队人数')->display(function ($value) {
                return number_format($value);
            });
            // $grid->column('reward_rate', '比例(%)')->display(function($value) {
            //     return number_format($value, 0);
            // });
            $grid->column('weekly_limit_usdt', '周USDT提现上限')->number();
            $grid->column('weekly_limit_nox', '周NOXO提现上限')->number();

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            // $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            // $grid->disableActions(); // 禁用操作按钮
            $grid->disableFilterButton(); // 禁用筛选按钮
            $grid->disableColumnSelector(); // 禁用列选择按钮
            $grid->disablePagination(); // 禁用分页
            // $grid->disableRefreshButton(); // 禁用刷新按钮
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Level(), function (Form $form) {
            if ($form->isEditing()) {
                $form->display('id', '级别')->disable();
                // $form->display('code', '编号')->disable();
                $form->display('user_perf', '个人业绩');
                $form->display('team_perf', '团队业绩');
                $form->display('direct_num', '直推人数')->help('有效直推人数');
                $form->display('team_num', '团队人数');
                // $form->number('reward_rate', '比例(%)')->rules('required|integer|between:1,99');
                $form->decimal('weekly_limit_usdt', '周USDT提现上限')->rules('required|numeric|min:0');
                $form->decimal('weekly_limit_nox', '周NOXO提现上限')->rules('required|numeric|min:0');
            }
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableDeleteButton();
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    // 不支持新增
                    return $form->response()->error('不支持新增');
                }
            });
        });
    }
}
