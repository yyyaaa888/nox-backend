<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\GridSortable\SaveOrderButton;
use App\Admin\Extensions\GridSortable\SortableAction;
use App\Models\Banner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Http\Request;

class BannersController extends AdminController
{
    protected $title = 'banner管理';

    protected $sortColumn = 'order';

    protected function grid()
    {
        $grid = new Grid(new Banner());

        $grid->model()->orderBy('order', 'asc');

        // $grid->column('id', 'ID')->sortable();
        $grid->column('pos_id', '位置')->using(Banner::POS_MAP);
        $grid->column('remark', '备注');
        $grid->column('img', '图片')->image('', 100, 100);
        $grid->column('href', '链接')->copyable()->link();
        $grid->column('is_show', '显示状态')->switch();
        $grid->column('order', '排序权重')->help('值越小越靠前')->sortable();

        // // 不显示新增按钮
        // $grid->disableCreateButton();
        // 不显示分页
        $grid->disablePagination();
        // 不显示删除按钮
        $grid->disableDeleteButton();
        // 不显示查看按钮
        $grid->disableViewButton();
        // 不显示行选择器
        $grid->disableRowSelector();

        if (! request()->has('_sort')) {
            $grid->model()->ordered();
        }
        $grid->tools(new SaveOrderButton($this->sortColumn, admin_base_path('banners/sort')));
        $grid->actions([
            new SortableAction(),
        ]);

        return $grid;
    }

    protected function form()
    {
        return Form::make(new Banner(), function (Form $form) {
            $form->select('pos_id', '位置')->required()->rules('required|integer|in:'.implode(',', array_keys(Banner::POS_MAP)))->options(Banner::POS_MAP);
            $form->text('remark', '备注')->maxLength(255)->rules('nullable|string|max:255')->help('后台备注，用户看不到');
            $form->image('img', '图片')->accept('png,jpg,jpeg', 'image/png,image/jpeg')->autoUpload()->help('建议上传690*386像素的PNG图片')->move('banners')->uniqueName();
            $form->text('href', '链接')->maxLength(1024)->rules('nullable|string|max:1024')->help('点击banner跳转的链接');
            $form->switch('is_show', '显示状态')->default(Banner::IS_SHOW_SHOW);
            // $form->number('order', '排序权重')->default(1)->help('值越小越靠前');

            // 不显示删除按钮
            $form->disableDeleteButton();
            // 不显示查看按钮
            $form->disableViewButton();
            // 底部不显示查看操作
            $form->disableViewCheck();
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    // 同一个位置最多支持5张广告图
                    $count = Banner::query()->where('pos_id', $form->input('pos_id'))->count();
                    if ($count >= 5) {
                        return $form->response()->error('同一个位置最多支持5张广告图');
                    }
                }
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Banner(), function (Show $show) {
            // $show->field('id', 'ID');
            $show->field('pos_id', '位置')->using(Banner::POS_MAP);
            $show->field('remark', '备注');
            $show->field('img', '图片')->image();
            $show->field('href', '链接');
            $show->field('is_show', '显示状态')->using(Banner::IS_SHOW_MAP);
            $show->field('order', '排序权重');
        });
    }

    public function sort(Request $request)
    {
        $status     = true;
        $column     = $this->sortColumn;
        $message    = trans('admin.save_succeeded');
        $repository = new Banner();

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
