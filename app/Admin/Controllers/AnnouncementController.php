<?php

namespace App\Admin\Controllers;

use App\Models\Announcement;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;

/**
 * @property string $title_cn
 * @property string $title_tw
 * @property string $title_en
 * @property string $title_ko
 * @property string $title_ja
 */
class AnnouncementController extends AdminController
{
    protected $title = '公告管理';

    protected function grid()
    {
        $grid = new Grid(new Announcement());

        $grid->column('id', 'ID')->sortable();
        $grid->column('title_cn', '标题')->expand(function () {
            // html转码
            $this->title_cn = htmlspecialchars($this->title_cn);
            $this->title_tw = htmlspecialchars($this->title_tw);
            $this->title_en = htmlspecialchars($this->title_en);
            $this->title_ko = htmlspecialchars($this->title_ko);
            $this->title_ja = htmlspecialchars($this->title_ja);
            $content        = "标题(英文)：{$this->title_en}<br/>";
            $content .= "标题(简体)：{$this->title_cn}<br/>";
            $content .= "标题(繁体)：{$this->title_tw}<br/>";
            $card = new Card(null, $content);

            return "<div style='padding:10px 10px 0'>$card</div>";
        });
        $grid->column('status', '状态')->using(Announcement::STATUS_MAP)->dot(Announcement::STATUS_COLOR);
        $grid->column('publish_time', '发布时间');

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Announcement());

        $form->text('title_en', '标题(英文)')->required();
        $form->text('title_cn', '标题(简体)')->required();
        $form->text('title_tw', '标题(繁体)')->required();
        $form->textarea('content_en', '内容(英文)')->required();
        $form->textarea('content_cn', '内容(简体)')->required();
        $form->textarea('content_tw', '内容(繁体)')->required();
        $form->datetime('publish_time', '发布时间')->required();
        $form->switch('status', '发布状态');

        return $form;
    }

    protected function detail($id)
    {
        return Show::make($id, new Announcement(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('title_en', '标题(英文)');
            $show->field('content_en', '内容(英文)');
            $show->field('title_cn', '标题(简体)');
            $show->field('content_cn', '内容(简体)');
            $show->field('title_tw', '标题(繁体)');
            $show->field('content_tw', '内容(繁体)');
            $show->field('status', '状态')->using(['未发布', '已发布']);
            $show->field('publish_time', '发布时间');
            $show->field('order', '排序权重');
        });
    }
}
