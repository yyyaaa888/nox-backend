<?php

namespace App\Admin\Metrics\Examples;

use App\Models\TeamReward;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeamRewardStat extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();

        $this->title('团队奖励统计');
        $this->subtitle('按结算日期统计');
        $this->dropdown([
            'today'      => '今日',
            'yesterday'  => '昨日',
            'week'       => '本周',
            'prev_week'  => '上周',
            'month'      => '本月',
            'last_month' => '上月',
            'year'       => '今年',
            'prev_year'  => '去年',
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
                    Carbon::yesterday()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ];
                break;
            case 'week':
                $times = [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ];
                break;
            case 'prev_week':
                $times = [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek(),
                ];
                break;
            case 'month':
                $times = [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ];
                break;
            case 'last_month':
                $times = [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ];
                break;
            case 'year':
                $times = [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ];
                break;
            case 'prev_year':
                $times = [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ];
                break;
            case 'today':
            default:
                $times = [
                    Carbon::now()->startOfDay(),
                    Carbon::now()->endOfDay(),
                ];
                break;
        }
        $amount = TeamReward::whereBetween('created_at', $times)->sum('reward_am');
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
