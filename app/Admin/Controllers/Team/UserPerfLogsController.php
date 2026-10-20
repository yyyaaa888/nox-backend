<?php

namespace App\Admin\Controllers\Team;

use App\Models\UserPerfLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class UserPerfLogsController extends AdminController
{
    protected $title = '业绩日志';

    protected function grid()
    {
        return Grid::make(new UserPerfLog(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            $grid->column('type', '类型')->using(UserPerfLog::TYPE_MAP);
            $grid->column('change_type', '变动类型')->using(UserPerfLog::CHANGE_TYPE_MAP)->label(UserPerfLog::CHANGE_TYPE_COLOR);
            $grid->column('amount', '业绩')->number();
            $grid->column('before_amount', '变动前业绩')->number();
            $grid->column('after_amount', '变动后业绩')->number();
            $grid->column('remark', '备注');

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

            $grid->export()->rows(function ($rows) { // 导出
                foreach ($rows as &$row) {
                    $row['user.address']  = $row['user']['address'] ?? null;
                    $row['type']          = UserPerfLog::TYPE_MAP[$row['type']] ?? null;
                    $row['change_type']   = UserPerfLog::CHANGE_TYPE_MAP[$row['change_type']] ?? null;
                    $row['amount']        = removeTrailingZeros($row['amount']);
                    $row['before_amount'] = removeTrailingZeros($row['before_amount']);
                    $row['after_amount']  = removeTrailingZeros($row['after_amount']);
                }

                return $rows;
            })->filename('业绩日志_'.date('Ymd_His'));

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                $filter->equal('type', '类型')->select(UserPerfLog::TYPE_MAP)->width(2);
                $filter->equal('change_type', '变动类型')->select(UserPerfLog::CHANGE_TYPE_MAP)->width(2);
                $filter->between('created_at')->datetime()->width(4);
            });
        });
    }
}
