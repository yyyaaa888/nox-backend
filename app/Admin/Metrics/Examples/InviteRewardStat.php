<?php

namespace App\Admin\Metrics\Examples;

use App\Models\InviteReward;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InviteRewardStat extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('分享奖励统计');
        $this->dropdown([
            'yesterday' => '昨日',
            'week'      => '本周',
            'month'     => '本月',
            'year'      => '今年',
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
        switch ($request->get('option')) {
            case 'yesterday':
                $times = [
                    Carbon::yesterday()->startOfDay()->toDateString(),
                    Carbon::yesterday()->endOfDay()->toDateString(),
                ];
                break;
            case 'week':
                $times = [
                    Carbon::now()->startOfWeek()->toDateString(),
                    Carbon::now()->endOfWeek()->toDateString(),
                ];
                break;
            case 'month':
                $times = [
                    Carbon::now()->startOfMonth()->toDateString(),
                    Carbon::now()->endOfMonth()->toDateString(),
                ];
                break;
            case 'year':
                $times = [
                    Carbon::now()->startOfYear()->toDateString(),
                    Carbon::now()->endOfYear()->toDateString(),
                ];
                break;
            case 'yesterday':
            default:
                $times = [
                    Carbon::yesterday()->startOfDay()->toDateString(),
                    Carbon::yesterday()->endOfDay()->toDateString(),
                ];
                break;
        }
        $amount = InviteReward::whereBetween('settlement_date', $times)->sum('reward_amount');
        $this->content(removeTrailingZeros($amount));
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
