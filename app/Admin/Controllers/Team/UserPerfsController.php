<?php

namespace App\Admin\Controllers\Team;

use App\Jobs\UserUpgradeJob;
use App\Models\Level;
use App\Models\UserData;
use App\Models\UserPerfLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\DB;

/**
 * @property \App\Models\User $user
 */
class UserPerfsController extends AdminController
{
    protected $title = '用户业绩';

    protected function grid()
    {
        return Grid::make(new UserData(['user']), function (Grid $grid) {

            $grid->model()->orderBy('id', 'desc');

            $grid->column('user_id', '用户ID')->sortable();
            $grid->column('user.address', '用户地址')->address();

            $grid->column('user.level_id', '等级')->using(Level::FULL_LEVEL_MAP)->sortable('user.level_id');
            $grid->column('perf', '用户业绩')->number()->sortable();

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用复选框
            $grid->disableViewButton(); // 禁用详情按钮
            // $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            // $grid->disableActions(); // 禁用操作按钮

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.address', '用户地址')->width(2);
                $filter->like('user.name', '用户名')->width(2);
                $filter->equal('user_id', '用户ID')->width(2);
                $filter->equal('user.level_id', '等级')->select(Level::FULL_LEVEL_MAP)->width(2);
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $builder = UserData::with('user');

        return Form::make($builder, function (Form $form) {
            if ($form->isEditing()) {
                $form->display('user.address', '用户地址')->disable();
                $form->display('user.name', '用户名')->disable();
                $form->display('perf', '用户业绩')->customFormat(function ($value) {
                    return removeTrailingZeros($value);
                });
                $form->text('add_perf', '添加业绩')->rules('nullable|integer', [
                    'integer' => '添加业绩必须为整数',
                ])->help('添加业绩为正数时，用户业绩增加；添加业绩为负数时，用户业绩减少。');
            }
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableDeleteButton();
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    // 不支持新增
                    return $form->response()->error('不支持新增');
                }

                $add_perf = $form->input('add_perf') ?: '0';

                if (bccomp($add_perf, '0', 6) === 0) { // 未改变
                    return $form->response()->success('用户业绩未改变');
                }

                /** @var UserData $model */
                $model = $form->model();

                $change_type = null;
                if ($add_perf > 0) {
                    $change_type = true;
                } else {
                    $change_type = false;
                    $add_perf    = bcsub('0', $add_perf, 6);
                    // 检查余额
                    if (bccomp($model->perf, $add_perf, 6) < 0) {
                        return $form->response()->error('用户业绩不足');
                    }
                }

                $admin_id = auth('admin')->user()->id;
                try {
                    DB::beginTransaction();
                    if ($change_type !== null) {
                        UserPerfLog::addLog($model, $add_perf, UserPerfLog::TYPE_SYS, $change_type, '系统变更，管理员'.$admin_id);
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();

                    return $form->response()->error($e->getMessage());
                }
                UserUpgradeJob::dispatch($model->user_id);
                $url = $form->getRedirectUrl($model->id);

                return $form->response()->redirectIf($url !== false, $url)->refreshIf($url === false)->success('变更成功');
            });
        });
    }
}
