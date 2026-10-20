<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ArticleCateRepository;
use App\Language;
use App\Models\ArticleCate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;

/**
 * @property string $name
 * @property mixed $attrs
 */
class ArticleCatesController extends AdminController
{
    protected $title = '公告类型';

    protected function grid()
    {
        $grid = new Grid(new ArticleCate(['attrs']));

        $grid->model();

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '名称')->expand(function () {
            $attrs   = $this->attrs->pluck(null, 'lang');
            $content = '';
            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                $name = htmlspecialchars($attrs[$lang_id]['name'] ?? '');
                $content .= "{$lang_cn_name}：{$name}<br/>";
            }
            $card = new Card(null, $content);

            return "<div style='padding:10px 10px 0'>$card</div>";
        });
        // $grid->column('color', '颜色')->display(function ($value) {
        //     if (!isset(ArticleCate::COLOR_MAP[$value])) {
        //         return '-';
        //     }
        //     $color = ArticleCate::COLOR_MAP[$value];
        //     $color_name = ArticleCate::COLOR_NAME_MAP[$value];
        //     return "<span style='color: $color; padding: 2px 4px; border-radius: 8px;border: 1px solid $color;'>$color_name</span>";
        // });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new ArticleCateRepository());

        foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
            $form->text("attrs.$lang_id.name", "名称($lang_cn_name)")->required()->maxLength(255);
        }
        // $form->select('color', '颜色')->options(ArticleCate::COLOR_NAME_MAP)->required();

        return $form;
    }

    protected function detail($id)
    {
        return Show::make($id, new ArticleCateRepository(), function (Show $show) {
            $show->field('id', 'ID');
            foreach (Language::CN_NAME_MAP as $lang_id => $lang_cn_name) {
                $show->field("attrs.$lang_id.name", "名称($lang_cn_name)");
            }
            // $show->field('color', '颜色')->unescape()->as(function ($value) {
            //     if (!isset(ArticleCate::COLOR_MAP[$value])) {
            //         return '-';
            //     }
            //     $color = ArticleCate::COLOR_MAP[$value];
            //     $color_name = ArticleCate::COLOR_NAME_MAP[$value];
            //     return "<span style='color: $color; padding: 2px 4px; border-radius: 8px;border: 1px solid $color;'>$color_name</span>";
            // });
        });
    }
}
