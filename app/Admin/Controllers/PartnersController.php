<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\GridSortable\SaveOrderButton;
use App\Admin\Extensions\GridSortable\SortableAction;
use App\Models\Partner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Http\Request;

/**
 * @property string $title
 * @property mixed $attrs
 */
class PartnersController extends AdminController
{
    protected $title = '合作机构管理';

    protected $sortColumn = 'order';

    protected function grid()
    {
        $grid = new Grid(new Partner(['attrs']));

        $grid->model()->orderBy('order');

        // $grid->column('id', 'ID')->sortable();
        $grid->column('name', '机构名称');
        $grid->column('logo_url', '机构图标')->image();
        $grid->column('is_show', '显示状态')->switch();
        $grid->column('order', '排序')->help('排序编号，数值越小越靠前')->sortable();
        // $grid->column('created_at');
        // $grid->column('updated_at');

        if (! request()->has('_sort')) {
            $grid->model()->ordered();
        }
        $grid->tools(new SaveOrderButton($this->sortColumn, admin_base_path('partners/sort')));
        $grid->actions([
            new SortableAction(),
        ]);

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Partner());

        $form->text('name', '机构名称')->required()->maxLength(255);
        $form->image('logo_url', '机构图标')->autoUpload()->move('partners')->uniqueName();
        $form->switch('is_show', '显示状态')->default(Partner::IS_SHOW_SHOW);
        $form->text('order', '排序')->default(999)->help('排序编号，数值越小越靠前');

        return $form;
    }

    protected function detail($id)
    {
        return Show::make($id, new Partner(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '机构名称');
            $show->field('logo_url', '机构图标')->image();
            $show->field('is_show', '显示状态')->using(Partner::IS_SHOW_MAP);
            $show->field('order', '排序');
        });
    }

    public function sort(Request $request)
    {
        $status     = true;
        $column     = $this->sortColumn;
        $message    = trans('admin.save_succeeded');
        $repository = new Partner();

        $sorts = $request->get('_sort');
        $sorts = collect($sorts)
            ->pluck('key')
            ->combine(
                collect($sorts)->pluck('sort')->sort()
            );

        try {
            $sorts->each(function ($v, $k) use ($repository, $column) {
                $form = new Form(new $repository());

                $form->text($column);

                $form->update($k, [$column => $v]);
            });

        } catch (\Exception $exception) {
            $status  = false;
            $message = $exception->getMessage();
        }

        return response()->json(compact('status', 'message'));
    }
}
