<?php

namespace App\Admin\Controllers\Team;

use App\Models\User;
use App\Models\UserRelationClosure;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property \App\Models\User $ancestor
 * @property \App\Models\User $descendant
 * @property int $descendant_id
 */
class TeamsController extends AdminController
{
    protected $title = '推荐关系';

    protected function grid()
    {
        return Grid::make(new UserRelationClosure(['ancestor', 'descendant']), function (Grid $grid) {

            $grid->model()->orderBy('ancestor_id')->orderBy('depth');

            $grid->column('ancestor_name', '团队长')->display(function () {
                if (! $this->ancestor) {
                    return null;
                }
                if ($this->ancestor->name && $this->ancestor->remark) {
                    return $this->ancestor->name.'('.$this->ancestor->remark.')';
                }

                return $this->ancestor->name ?? null;
            });
            $grid->column('direct_parent', '团员直属上级')->display(function () {
                $parent_id = UserRelationClosure::query()->where('descendant_id', $this->descendant_id)->where('depth', 1)->value('ancestor_id');
                if (! $parent_id) {
                    return null;
                }
                $parent = User::query()->where('id', $parent_id)->first();
                if ($parent->name && $parent->remark) {
                    return $parent->name.'('.$parent->remark.')';
                }

                return $parent->name ?? null;
            });
            $grid->column('descendant_name', '团员')->display(function () {
                if (! $this->descendant) {
                    return null;
                }
                if ($this->descendant->name && $this->descendant->remark) {
                    return $this->descendant->name.'('.$this->descendant->remark.')';
                }

                return $this->descendant->name ?? null;
            });
            $grid->column('depth', '层级');
            $grid->column('performance', '团员业绩')->display(function () {
                return (int) User::getPersonalPerformance($this->descendant_id);
            });
            $grid->column('team_performance', '团队业绩')->display(function () {
                return (int) User::getTeamPerformance($this->descendant_id);
            });

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
                $filter->like('ancestor.name', '团队长')->width(2);
                $filter->like('descendant.name', '团员')->width(2);
                $filter->equal('depth', '层级')->width(2);
            });
        });
    }
}
