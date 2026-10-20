<?php

namespace App\Admin\Controllers;

use App\Models\AlphaProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class AlphaProductsController extends AdminController
{
    protected $title = '资产包产品';

    protected function grid()
    {
        $grid = new Grid(new AlphaProduct());
        $grid->model()->orderBy('sort', 'asc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '名称');
        // $grid->column('tier', '档位')->using(AlphaProduct::TIER_MAP);
        $grid->column('min_amount', '最小申购额')->number();
        $grid->column('max_amount', '最大申购额')->number();
        $grid->column('per_power', '每单位算力值')->number();
        $grid->column('min_rate', '最低月产率(%)')->number()->help('只作展示用');
        $grid->column('max_rate', '最高月产率(%)')->number()->help('只作展示用');
        $grid->column('base_rate', '基础月产率(%)')->number();
        $grid->column('node_rate', '节点加速月产率(%)')->number();
        // $grid->column('status', '状态')->using(AlphaProduct::STATUS_MAP)->dot();
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

        // $grid->filter(function (Grid\Filter $filter) {
        //     $filter->equal('tier', '档位')->select(AlphaProduct::TIER_MAP);
        // });

        return $grid;
    }

    protected function form()
    {
        return Form::make(new AlphaProduct(), function (Form $form) {
            // $form->text('name', '名称')->required()->maxLength(50);
            $form->display('name', '名称');
            // $form->select('tier', '档位')->options(AlphaProduct::TIER_MAP)->required()->rules('in:' . implode(',', array_keys(AlphaProduct::TIER_MAP)));
            $form->decimal('min_amount', '最小申购额')->required()->rules('required|numeric|min:0');
            $form->decimal('max_amount', '最大申购额')->required()->rules('required|numeric|min:0|gte:min_amount');
            $form->decimal('per_power', '每单位算力值')->required()->rules('required|numeric|min:0')->help('用户购买后获得的算力值 = 购买金额 * 每单位算力值');
            $form->decimal('min_rate', '最低月产率(%)')->default(0)->rules('numeric|min:0')->help('只作展示用');
            $form->decimal('max_rate', '最高月产率(%)')->default(0)->rules('numeric|min:0')->help('只作展示用');
            $form->decimal('base_rate', '基础月产率(%)')->required()->rules('required|numeric|min:0');
            $form->decimal('node_rate', '节点加速月产率(%)')->default(0)->rules('numeric|min:0');
            // $form->switch('status', '状态')->default(AlphaProduct::STATUS_ON);
            // $form->number('sort', '排序')->default(0);
            $form->disableDeleteButton();
            $form->disableViewButton();
            $form->disableViewCheck();
        });
    }

    // protected function detail($id)
    // {
    //     return Show::make($id, new AlphaProduct(), function (Show $show) {
    //         $show->field('id', 'ID');
    //         $show->field('name', '名称');
    //         $show->field('tier', '档位')->using(AlphaProduct::TIER_MAP);
    //         $show->field('min_amount', '最小申购额');
    //         $show->field('max_amount', '最大申购额');
    //         $show->field('base_rate', '基础月产率(%)');
    //         $show->field('node_rate', '节点加速月产率(%)');
    //         $show->field('status', '状态')->using(AlphaProduct::STATUS_MAP);
    //         $show->field('sort', '排序');
    //     });
    // }
}
