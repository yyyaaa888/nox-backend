<?php

namespace App\Admin\Controllers\Asset;

use App\Models\UserRecharge;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class UserRechargesController extends AdminController
{
    protected $title = '用户充值记录';

    protected function grid()
    {
        return Grid::make(new UserRecharge(['user']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            // $grid->column('user.name', '用户名')->display(function () {
            //     if (!$this->user) {
            //         return null;
            //     }
            //     if ($this->user->name && $this->user->remark) {
            //         return $this->user->name . '(' . $this->user->remark . ')';
            //     }
            //     return $this->user->name ?? null;
            // });
            $grid->column('user.address', '用户地址')->address();
            $grid->column('sn', '单号');
            $grid->column('symbol', '资产类型')->using(UserRecharge::getSymbolMap());
            $grid->column('amount', '充值金额')->number();
            $grid->column('status', '状态')->using(UserRecharge::STATUS_MAP)->dot(UserRecharge::STATUS_COLOR);

            $grid->column('created_at')->sortable();

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用复选框
            $grid->disableViewButton(); // 禁用详情按钮
            $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            $grid->disableActions(); // 禁用

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                $filter->like('sn', '单号')->width(2);
                $filter->equal('symbol', '资产类型')->select(UserRecharge::getSymbolMap())->width(2);
                $filter->equal('status', '状态')->select(UserRecharge::STATUS_MAP)->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }
}
