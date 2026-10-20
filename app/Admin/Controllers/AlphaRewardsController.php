<?php

namespace App\Admin\Controllers;

use App\Models\AlphaProduct;
use App\Models\AlphaReward;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

class AlphaRewardsController extends AdminController
{
    protected $title = '资产包收益';

    protected function grid()
    {
        return Grid::make(new AlphaReward(['user', 'order.product']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            $grid->column('order.sn', '单号')->copyable();
            $grid->column('order.product.name', '产品')->trim();
            $grid->column('reward_am', '静态收益')->number();
            $grid->column('order_am', '订单金额')->number();
            $grid->column('rate', '月产率(%)')->number();
            $grid->column('quota_used_am', '计入额度槽金额')->number();
            $grid->column('frozen_am', '本次冻结金额')->number();

            $grid->column('settle_date', '结算日期')->sortable();
            $grid->column('created_at')->sortable();

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
                $filter->like('user.name', '用户名')->width(3);
                $filter->like('user.address', '用户地址')->width(3);
                $filter->like('order.sn', '单号')->width(2);
                $filter->equal('order.product.id', '产品')->select(AlphaProduct::query()->pluck('name', 'id'))->width(2);
                $filter->between('settle_date', '结算日期')->date()->width(3);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
