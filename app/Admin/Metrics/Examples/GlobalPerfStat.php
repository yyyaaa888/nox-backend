<?php

namespace App\Admin\Metrics\Examples;

use App\Models\UserData;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;

class GlobalPerfStat extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('全网业绩实时统计');
        // $this->subtitle('实时统计');
        // $this->dropdown([
        //     'now' => '当前',
        // ]);
    }

    /**
     * 处理请求
     *
     *
     * @return mixed|void
     */
    public function handle(Request $request)
    {
        $amount = UserData::sum('nft_amount');
        $this->content(removeTrailingZeros(number_format($amount, 6)));
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
                <h2 class="ml-1 font-lg-1 ">{$content}</h2>
            </div>
        HTML;
    }
}
