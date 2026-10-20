<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ContentRepository;
use App\Language;
use App\Models\Content;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

/**
 * @property string $title
 * @property mixed $attrs
 */
class ContentsController extends AdminController
{
    protected $title = '文字管理';

    protected function grid()
    {
        $grid = new Grid(new Content(['attrs']));

        // $grid->column('id', 'ID')->sortable();
        $grid->column('code', '位置')->using(Content::CODE_MAP);
        // $grid->column('title', '标题');
        // $grid->column('created_at');
        $grid->column('updated_at');

        $grid->disableRowSelector(); // 禁用行选择按钮
        $grid->disableViewButton(); // 禁用详情按钮
        $grid->disableDeleteButton(); // 禁用删除按钮
        $grid->disableCreateButton(); // 禁用创建按钮
        $grid->disableBatchDelete(); // 禁用批量删除按钮

        return $grid;
    }

    protected function form()
    {
        return Form::make(new ContentRepository(), function (Form $form) {
            if ($form->isEditing()) {
                $model = $form->model();
                $form->display('code', '位置')->with(function ($value) {
                    return Content::CODE_MAP[$value] ?? null;
                });
                $form->display('updated_at', '更新时间');
                $this->buildForm($model->code, $form);
            } else {
                $form->select('code', '位置')->required()->rules('required|in:'.implode(',', array_keys(Content::CODE_MAP)))->options(Content::CODE_MAP);
                foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                    $form->textarea("attrs.$lang_id.content", "内容($lang_cn_name)");
                }
            }
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableDeleteButton();
            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    // 不支持新增
                    return $form->response()->error('不支持新增');
                }
            });
        });
    }

    protected function buildForm(string $code, Form $form)
    {
        switch ($code) {
            // case Content::CODE_ADVANTAGES:
            //     $form->image('data', '图片')
            //         ->help('建议上传130*130像素的PNG图片')
            //         ->required()
            //         ->accept('png,jpg,jpeg', 'image/png,image/jpeg')
            //         ->move('contents')
            //         ->autoUpload()
            //         ->uniqueName();
            //     foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
            //         $form->text("attrs.$lang_id.title", "标题($lang_cn_name)")->required()->rules('required|string|max:200')->help('不能超过200个字符');
            //     }
            //     foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
            //         $form->textarea("attrs.$lang_id.content", "内容($lang_cn_name)");
            //     }
            //     break;
            case Content::CODE_SHORT_TERM_GOAL:
            case Content::CODE_MID_TERM_GOAL:
            case Content::CODE_LONG_TERM_GOAL:
                $form->image('data', '图片')
                    ->help('建议上传190*169像素的PNG图片')
                    ->required()
                    ->accept('png,jpg,jpeg', 'image/png,image/jpeg')
                    ->move('contents')
                    ->autoUpload()
                    ->uniqueName();
                foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                    $form->list("attrs.$lang_id.content", "内容($lang_cn_name)")->max(3)->saveAsJson()->help('最多添加3项内容');
                }
                break;
            case Content::CODE_LUCKY_RULES:
                foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                    $form->list("attrs.$lang_id.content", "内容($lang_cn_name)")->saveAsJson(JSON_UNESCAPED_UNICODE);
                }
                break;
            case Content::CODE_PLAT_INTRO:
                foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                    $form->textarea("attrs.$lang_id.content", "内容($lang_cn_name)");
                }
                $form->file('data', '视频')
                    ->help('请上传20M以内的MP4、AVI视频')
                    ->required()
                    ->accept('mp4,avi', 'video/mp4,video/avi')
                    ->maxSize(20 * 1024)
                    ->move('contents')
                    ->autoUpload()
                    ->uniqueName();
                break;
            case Content::CODE_LINES:
                $form->array('data', '路线图', function ($table) {
                    $table->text('date', '日期')->required()->rules('required|max:255');
                    foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                        $table->text('content_'.$lang_id, "内容($lang_cn_name)");
                    }
                })->saveAsJson(JSON_UNESCAPED_UNICODE);
                break;
            default:
                foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                    $form->textarea("attrs.$lang_id.content", "内容($lang_cn_name)");
                }
                break;
        }
    }
}
