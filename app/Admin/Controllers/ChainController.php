<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Chain;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ChainController extends AdminController
{
    protected $title = '区块链配置';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Chain(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('alias', '别名');
            $grid->column('chain_id', '链ID');
            $grid->column('symbol', '币种');
            $grid->column('decimals', '精度');
            $grid->column('rpc_url', 'RPC'); // ->limit(10);
            $grid->column('scan_url', '浏览器地址'); // ->limit(10);
            // $grid->column('address','合约地址')->limit(5);
            // $grid->column('status','开关')->switch();
            // $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->addTableClass(['table-text-center']); // 居中对齐
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            $grid->disableActions(); // 禁用操作按钮
            $grid->disablePagination(); // 禁用分页
        });
    }

    // /**
    //  * Make a show builder.
    //  *
    //  * @param mixed $id
    //  *
    //  * @return Show
    //  */
    // protected function detail($id)
    // {
    //     return Show::make($id, new Chain(), function (Show $show) {
    //         $show->field('id');
    //     });
    // }
    //
    // /**
    //  * Make a form builder.
    //  *
    //  * @return Form
    //  */
    // protected function form()
    // {
    //     return Form::make(new Chain(), function (Form $form) {
    //         $form->display('id');
    //         $form->text('name', '名称')->required();
    //         $form->text('alias', '别名');

    //         $form->disableDeleteButton();
    //         $form->disableViewButton();
    //         $form->disableViewCheck();
    //         $form->disableEditingCheck();
    //         $form->disableCreatingCheck();
    //     });
    // }
}
