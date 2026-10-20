<?php

namespace App\Admin\Controllers;

use App\Models\NodeProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class NodeProductsController extends AdminController
{
    protected $title = '节点产品';

    protected function grid()
    {
        $grid = new Grid(new NodeProduct());
        $grid->model()->orderBy('sort', 'asc');

        $grid->column('id', 'ID');
        $grid->column('name', '名称');
        $grid->column('price', '价格')->number();
        // $grid->column('status', '状态')->using(NodeProduct::STATUS_MAP)->dot();
        // $grid->column('sort', '排序')->sortable();

        $grid->addTableClass(['table-text-center']);
        $grid->withBorder(); // 显示边框
        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        // $grid->disableEditButton(); // 禁用编辑按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮
        // $grid->disableActions(); // 禁用操作按钮

        return $grid;
    }

    protected function form()
    {
        return Form::make(new NodeProduct(), function (Form $form) {
            // $form->text('name', '名称')->required()->maxLength(50);
            $form->display('name', '名称');
            $form->number('price', '价格')->required()->rules('required|integer|min:0');
            // $form->switch('status', '状态')->default(NodeProduct::STATUS_ON);
            // $form->number('sort', '排序')->default(0);
            $form->disableDeleteButton();
            $form->disableViewButton();
            $form->disableViewCheck();
        });
    }

    // protected function detail($id)
    // {
    //     return Show::make($id, new NodeProduct(), function (Show $show) {
    //         $show->field('id', 'ID');
    //         $show->field('name', '名称');
    //         $show->field('price', '价格');
    //         $show->field('status', '状态')->using(NodeProduct::STATUS_MAP);
    //         $show->field('sort', '排序');
    //     });
    // }
}
