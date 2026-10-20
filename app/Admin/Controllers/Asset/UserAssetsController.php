<?php

namespace App\Admin\Controllers\Asset;

use App\Admin\Forms\RechargeForm;
use App\Models\UserData;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;

/**
 * @property \App\Models\User $user
 */
class UserAssetsController extends AdminController
{
    protected $title = '用户资产';

    protected function grid()
    {
        return Grid::make(new UserData(['user']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();

            $grid->column('current_usdt', '当前USDT')->number();
            $grid->column('current_nox', '当前NOXO')->number();

            $grid->column('freeze_usdt', '冻结USDT')->number();
            $grid->column('freeze_nox', '冻结NOXO')->number();

            $grid->column('created_at')->sortable();

            $grid->tools([
                '<a class="btn btn-primary disable-outline" href="'.admin_url('userAssets/recharge').'">充值扣款</a>',
            ]);

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
                $filter->like('user.address', '用户地址')->width(2);
                $filter->like('user.name', '用户名')->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }

    public function recharge(Content $content)
    {
        return $content
            ->title('充值')
            ->body(new Card(new RechargeForm()));
    }
}
