<?php

namespace App\Admin\Metrics\Examples;

use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;

class NewUsers extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('用户');
        $this->dropdown([
            '1' => '总用户人数',
            '2' => '正常总用户人数',
            '3' => '被禁用总用户人数',
        ]);
    }

    /**
     * 处理请求
     *
     *
     * @return mixed|void
     */
    public function handle(Request $request)
    {
        $userModel = new \App\Models\User();

        switch ($request->get('option')) {
            case '2':
                $num = $userModel->userCount(2);
                $this->content($num);
                break;
            case '3':
                $num = $userModel->userCount(3);
                $this->content($num);
                break;
            case '1':
            default:
                $num = $userModel->userCount(1);
                $this->content($num);
        }
    }

    /**
     * 渲染卡片内容.
     *
     * @return string
     */
    public function renderContent()
    {
        $content = parent::renderContent();

        return <<<HTML
            <div class="d-flex justify-content-between align-items-center mt-1 " style="margin-bottom: 2px;">
                <h2 class="ml-1 font-lg-1 "> {$content}</h2>
            </div>
            <div class="ml-1 mt-1 font-weight-bold text-80" style="margin-bottom: 2px; background-color: #00c4ff">
                {$this->renderFooter()}
            </div>
        HTML;
    }

    /**
     * @return string
     */
    public function renderFooter()
    {
        return $this->toString($this->footer);
    }
}
