<?php

namespace App\Admin\Metrics\Examples;

use App\Models\UserPerfLog;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserPerfStat extends Card
{
    /**
     * 初始化卡片内容
     *
     * @return void
     */
    protected function init()
    {
        parent::init();
        $this->title('新增业绩统计');
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
        // $inc_amount = UserPerfLog::whereBetween('created_at', $times)->where('change_type', UserPerfLog::CHANGE_TYPE_IN)->sum('amount');
        // $dec_amount = UserPerfLog::whereBetween('created_at', $times)->where('change_type', UserPerfLog::CHANGE_TYPE_OUT)->sum('amount');
        $result = UserPerfLog::whereBetween('created_at', $times)
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as inc_amount', [UserPerfLog::CHANGE_TYPE_IN])
            ->selectRaw('SUM(CASE WHEN change_type = ? THEN amount ELSE 0 END) as dec_amount', [UserPerfLog::CHANGE_TYPE_OUT])
            ->first();

        $inc_amount = $result->inc_amount ?? '0';
        $dec_amount = $result->dec_amount ?? '0';
        $amount     = bcsub($inc_amount, $dec_amount, 6);
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
