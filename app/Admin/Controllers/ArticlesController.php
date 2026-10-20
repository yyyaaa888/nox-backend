<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ArticleRepository;
use App\Language;
use App\Models\Article;
use App\Models\ArticleCate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;

/**
 * @property ArticleCate $cate
 * @property string $title
 * @property mixed $attrs
 */
class ArticlesController extends AdminController
{
    protected $title = '公告管理';

    protected $sortColumn = 'order';

    protected function grid()
    {
        $grid = new Grid(new Article(['attrs', 'cate']));
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID')->sortable();
        $grid->column('cate.name', '类型')->trim();
        $grid->column('title', '标题')->expand(function () {
            $attrs   = $this->attrs->pluck(null, 'lang');
            $content = '';
            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                $title = htmlspecialchars($attrs[$lang_id]['title'] ?? '');
                $content .= "{$lang_cn_name}：{$title}<br/>";
            }
            $card = new Card(null, $content);

            return "<div style='padding:10px 10px 0'>$card</div>";
        });
        // $grid->column('mini_img', '列表图片')->image('', 50, 50);
        $grid->column('is_show', '显示状态')->switch();
        $grid->column('is_top', '置顶')->switch();
        $grid->column('order', '排序权重')->help('值越大越靠前')->sortable()->editable();
        $grid->column('publish_time', '发布时间')->help('发布后将在指定时间显示')->sortable();
        // $grid->column('created_at');
        // $grid->column('updated_at');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->equal('cate_id', '类型')->select(ArticleCate::pluck('name', 'id')->toArray())->width(2);
            $filter->equal('is_show', '显示状态')->select(Article::IS_SHOW_MAP)->width(2);
            $filter->equal('is_top', '置顶')->select(Article::IS_TOP_MAP)->width(2);
            $filter->between('publish_time', '发布时间')->datetime()->width(4);
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new ArticleRepository());

        $form->select('cate_id', '类型')->options(ArticleCate::pluck('name', 'id'))->required();
        foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
            $form->text("attrs.$lang_id.title", "标题($lang_cn_name)")->required()->maxLength(255);
        }
        foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
            $form->textarea("attrs.$lang_id.content", "内容($lang_cn_name)");
        }
        // $form->image('mini_img', '列表图片')->autoUpload()->move('articles')->uniqueName();
        // $form->multipleImage('images', '正文图片')->autoUpload()->move('articles')->uniqueName()->sortable()->help('最多支持上传10张图片');
        $form->switch('is_show', '显示状态')->default(Article::IS_SHOW_SHOW);
        $form->switch('is_top', '置顶')->default(Article::IS_TOP_NO);
        $form->number('order', '排序权重')->default(0)->help('值越大越靠前');
        $form->datetime('publish_time', '发布时间')->default(date('Y-m-d H:i:s'))->help('发布后将在指定时间显示');

        return $form;
    }

    protected function detail($id)
    {
        return Show::make($id, new ArticleRepository(), function (Show $show) {
            $show->field('id', 'ID');
            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                $show->field("attrs.$lang_id.title", "标题($lang_cn_name)");
                $show->field("attrs.$lang_id.content", "内容($lang_cn_name)");
            }
            $show->field('cate.name', '类型');
            // $show->field('mini_img', '列表图片')->image();
            // $show->field('images', '正文图片')->image();
            $show->field('is_show', '显示状态')->using(Article::IS_SHOW_MAP);
            $show->field('is_top', '置顶')->using(Article::IS_TOP_MAP);
            $show->field('order', '排序权重');
            $show->field('publish_time', '发布时间');
        });
    }
}
