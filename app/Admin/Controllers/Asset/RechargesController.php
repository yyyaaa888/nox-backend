<?php

namespace App\Admin\Controllers\Asset;

use App\Exceptions\LogicException;
use App\Models\Recharge;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property \App\Models\User $user
 * @property \Dcat\Admin\Models\Administrator $admin
 */
class RechargesController extends AdminController
{
    protected $title = '系统充值扣款记录';

    protected function grid()
    {
        return Grid::make(new Recharge(['user', 'admin']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            // $grid->column('name', '用户名')->display(function () {
            //     if (!$this->user) {
            //         return null;
            //     }
            //     if ($this->user->name && $this->user->remark) {
            //         return $this->user->name . '(' . $this->user->remark . ')';
            //     }
            //     return $this->user->name ?? null;
            // });
            $grid->column('user.address', '用户地址')->display(function () {
                return $this?->user?->address;
            })->address();
            $grid->column('admin.name', '管理员')->display(function () {
                if (! $this->admin) {
                    return null;
                }

                return $this->admin->name ?? null;
            });
            $grid->column('type', '类型')->using(Recharge::TYPE_MAP);
            $grid->column('asset', '资产类型')->using(Recharge::ASSET_MAP);
            $grid->column('amount', '金额')->number();

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
                $filter->like('admin.name', '管理员')->width(2);
                $filter->equal('type', '类型')->select(Recharge::TYPE_MAP)->width(2);
                $filter->equal('asset', '资产类型')->select(Recharge::ASSET_MAP)->width(2);
                $filter->between('created_at', '创建时间')->datetime()->width(4);
            });
        });
    }

    // /**
    //  * Make a form builder.
    //  *
    //  * @return Form
    //  */
    // protected function form()
    // {
    //     return Form::make(new Recharge(), function (Form $form) {
    //         if ($form->isCreating()) {
    //             // 充值用户
    //             $form->select('user_id', '用户')->options(function ($id) {
    //                 $user = \App\Models\User::find($id);
    //                 if ($user) {
    //                     return [$user->id => $user->name . '(' . $user->remark . ')'];
    //                 }
    //                 return [];
    //             })->ajax('/api/users')->required();
    //             // 账户类型
    //             $form->radio('symbol', '资产类型')->options(Recharge::ASSET_MAP)->required();
    //             $form->number('amount', '充值金额')->help('最多支持两位小数')->required();
    //             $form->saving(function (Form $form) {
    //                 if ($form->isEditing()) {
    //                     return $form->response()->error('不支持编辑');
    //                 }
    //                 $user_id = $form->user_id;
    //                 $amount = $form->amount;
    //                 $symbol = $form->symbol;
    //                 if (!$user_id) {
    //                     return $form->response()->error('用户不能为空');
    //                 }
    //                 $user = \App\Models\User::find($user_id);
    //                 if (!$user) {
    //                     return $form->response()->error('用户不存在');
    //                 }
    //                 $red = RedLockService::instance();
    //                 $lock = $red->lock('sys_recharge_' . $user_id, 5000);
    //                 if (!$lock) {
    //                     return $form->response()->error('系统繁忙，请稍后再试');
    //                 }
    //                 try {
    //                     DB::beginTransaction();
    //                     RechargeService::systemCreateOrder($user, $symbol, $amount);
    //                     DB::commit();
    //                     $red->unlock($lock);
    //                     return $form->response()->success('充值成功')->redirect('recharges/create');
    //                 } catch (LogicException $e) {
    //                     DB::rollBack();
    //                     $red->unlock($lock);
    //                     return $form->response()->error($e->getMessage());
    //                 } catch (\Exception $e) {
    //                     DB::rollBack();
    //                     $red->unlock($lock);
    //                     Log::error('sys_recharge_create_error', [$e->getMessage(), $e->getTraceAsString()]);
    //                     return $form->response()->error('服务器异常');
    //                 }
    //             });
    //         }
    //         $form->disableViewButton();
    //         $form->disableViewCheck();
    //         $form->disableEditingCheck();
    //         $form->disableCreatingCheck();
    //         $form->disableDeleteButton();
    //     });
    // }
}
