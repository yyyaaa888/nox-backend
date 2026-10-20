<?php

namespace App\Admin\Controllers\Asset;

use App\Models\Asset;
use App\Models\Chain;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

/**
 * @property Chain $chain
 */
class AssetController extends AdminController
{
    protected $title = '代币管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Asset(['chain']), function (Grid $grid) {
            $chain_id = config('site.asset.chain_id');
            $grid->model()->where('chain_id', $chain_id);
            $grid->column('id')->sortable();
            $grid->column('title', '标题');
            $grid->column('chain.alias', '区块链')->trim();
            $grid->column('code', '币种类型')->using(Asset::CODE_MAP);
            $grid->column('name', '币种单位');
            $grid->column('decimals', '精度');
            $grid->column('exchange_rate', '汇率(USDT)')->number();
            $grid->column('contract_address', '合约地址')->address();
            // $grid->column('status', '状态')->switch();

            $grid->addTableClass(['table-text-center']); // 居中对齐
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            // $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            // $grid->disableActions(); // 禁用操作按钮
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
    //     return Show::make($id, new Asset(), function (Show $show) {
    //         $show->field('id');
    //     });
    // }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Asset(), function (Form $form) {
            $model = $form->model();
            $form->display('id');
            // $form->radio('code', '币种类型')->options(Asset::CODE_MAP)->default(Asset::CODE_USDT)->required();
            $form->display('title', '标题');
            // $form->text('name', '币种单位')->required();
            // $form->number('decimals', '精度')->required();
            // $form->radio('type', '类型')->options(Asset::TYPE_MAP)->default(Asset::TYPE_TOKEN)->required();
            if ($model->code != Asset::CODE_USDT) {
                $form->decimal('exchange_rate', '汇率(USDT)')->required()->rules('required|decimal:0,6|gt:0')->help('币种兑USDT汇率，支持小数点后6位');
            } else {
                $form->display('exchange_rate', '汇率(USDT)')->customFormat(function ($value) {
                    return removeTrailingZeros($value);
                })->help('币种兑USDT汇率');
            }
            // $form->text('contract_address', '合约地址');
            // $form->textarea('contract_abi', '合约ABI');
            // $form->switch('status', '状态');

            $form->disableDeleteButton();
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
}
