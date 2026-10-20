<?php

namespace App\Admin\Controllers\Team;

use App\Models\Level;
use App\Models\LevelHistory;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $user
 */
class LevelHistoriesController extends AdminController
{
    protected $title = '等级记录';

    protected function grid()
    {
        return Grid::make(new LevelHistory(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('user.address', '用户地址')->address();
            $grid->column('old_level', '原等级')->using(Level::FULL_LEVEL_MAP)->sortable();
            $grid->column('new_level', '新等级')->using(Level::FULL_LEVEL_MAP)->sortable();
            $grid->column('remark', '备注');

            $grid->column('created_at', '升级时间')->sortable();

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
                    $row['user.address'] = $row['user']['address'] ?? null;
                    $row['old_level']    = user_level($row['old_level']);
                    $row['new_level']    = user_level($row['new_level']);
                }

                return $rows;
            })->filename('等级记录_'.date('Ymd_His'));

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('user.name', '用户名')->width(2);
                $filter->like('user.address', '用户地址')->width(2);
                $filter->equal('old_level', '原等级')->select(Level::FULL_LEVEL_MAP)->width(2);
                $filter->equal('new_level', '新等级')->select(Level::FULL_LEVEL_MAP)->width(2);
                $filter->between('created_at', '升级时间')->datetime()->width(4);
            });
        });
    }
}
