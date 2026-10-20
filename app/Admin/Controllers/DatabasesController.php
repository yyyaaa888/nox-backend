<?php

namespace App\Admin\Controllers;

use App\Language;
use App\Models\Database;
use Dcat\Admin\Form;
use Dcat\Admin\Form\NestedForm;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * @property \App\Models\User $user
 * @property int $node_id
 */
class DatabasesController extends AdminController
{
    protected $title = '数据库';

    protected function grid()
    {
        return Grid::make(new Database(), function (Grid $grid) {
            $grid->column('type')->using(Database::TYPE_MAP);

            $grid->addTableClass(['table-text-center']);
            $grid->withBorder(); // 显示边框
            $grid->disableRowSelector(); // 禁用行选择按钮
            $grid->disableViewButton(); // 禁用详情按钮
            // $grid->disableEditButton(); // 禁用编辑按钮
            $grid->disableDeleteButton(); // 禁用删除按钮
            // $grid->disableCreateButton(); // 禁用创建按钮
            $grid->disableBatchDelete(); // 禁用批量删除按钮
            // $grid->disableActions(); // 禁用操作按钮
        });
    }

    protected function form()
    {
        return Form::make(new Database(), function (Form $form) {
            if ($form->isCreating()) {
                $form->select('type', '类型')
                    ->options(Database::TYPE_MAP)
                    ->default(Database::TYPE_INTRO)
                    ->required()
                    ->rules('required|'.Rule::in(array_keys(Database::TYPE_MAP)).'|unique:databases,type');
            } else {
                $type = $form->model()->type;
                switch ($type) {
                    case Database::TYPE_INTRO:
                        $form->array('data', Database::TYPE_MAP[$type] ?? '内容', function (NestedForm $table) {
                            $table->select('lang', '语言')
                                ->options(Language::CN_NAME_MAP)
                                ->required()
                                ->rules('required|'.Rule::in(array_keys(Language::CN_NAME_MAP)));

                            $table->text('title', '标题')
                                ->required()
                                ->rules('required|string|max:200')
                                ->help('不能超过200个字符');

                            $table->textarea('content', '内容')
                                ->required();
                        });
                        break;
                    case Database::TYPE_DOCUMENT:
                        $form->array('data', Database::TYPE_MAP[$type] ?? '内容', function (NestedForm $table) {
                            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                                $table->text('title_'.$lang_id, "标题($lang_cn_name)")
                                    ->required()
                                    ->rules('required|string|max:200')
                                    ->help('不能超过200个字符');
                            }

                            $table->file('document', '文档')->required()->accept('pdf', 'application/pdf')->autoUpload()->help('请上传10M以内的PDF文件')->move('databases/documents')->uniqueName();
                            $table->datetime('time', '创建时间')->required()->default(Carbon::now());
                        });
                        break;
                    case Database::TYPE_POSTER:
                        $form->array('data', Database::TYPE_MAP[$type] ?? '内容', function (NestedForm $table) {
                            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                                $table->text('title_'.$lang_id, "标题($lang_cn_name)")
                                    ->required()
                                    ->rules('required|string|max:200')
                                    ->help('不能超过200个字符');
                            }

                            $table->image('poster', '海报')->required()->accept('png,jpg,jpeg', 'image/png,image/jpeg')->autoUpload()->help('请上传10M以内的PNG、JPG、JPEG格式图片')->move('databases/posters')->uniqueName();
                            $table->datetime('time', '创建时间')->required()->default(Carbon::now());
                        });
                        break;
                    case Database::TYPE_VIDEO:
                        $form->array('data', Database::TYPE_MAP[$type] ?? '内容', function (NestedForm $table) {
                            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                                $table->text('title_'.$lang_id, "标题($lang_cn_name)")
                                    ->required()
                                    ->rules('required|string|max:200')
                                    ->help('不能超过200个字符');
                            }

                            $table->file('video', '视频')->required()->accept('mp4,avi', 'video/mp4,video/avi')->maxSize(20 * 1024)->autoUpload()->help('请上传20M以内的MP4、AVI格式视频')->move('databases/videos')->uniqueName();
                            $table->datetime('time', '创建时间')->required()->default(Carbon::now());
                        });
                        break;
                    case Database::TYPE_VIDEO_MEETING:
                        $form->array('data', Database::TYPE_MAP[$type] ?? '内容', function (NestedForm $table) {
                            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                                $table->text('title_'.$lang_id, "标题($lang_cn_name)")
                                    ->required()
                                    ->rules('required|string|max:200')
                                    ->help('不能超过200个字符');
                            }

                            $table->text('link', '链接')->required()->help('视频链接');
                            $table->datetime('time', '时间')->required()->default(Carbon::now());
                        });
                        break;
                }
            }

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
            $form->disableDeleteButton();
            $form->disableViewButton();
        });
    }
}
