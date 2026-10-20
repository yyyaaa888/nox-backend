<?php

namespace App\Admin\Metrics\Examples;

use App\Models\PayOrder;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentStat extends Card
{
    protected function init()
    {
        parent::init();

        $this->title('支付统计');
        $this->subtitle('按支付时间统计');
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
        $sql = 'sum(amount - balance) as amount';
        $row = PayOrder::whereBetween('pay_at', $times)->selectRaw($sql)->where('status', PayOrder::STATUS_PAID)->first();
        $this->withContent($row->amount);
    }

    protected function withContent($amount)
    {
        $amount = removeTrailingZeros($amount);

        $table = <<<HTML
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-0">{$amount}</h2>
            <p class="text-sm text-gray-500">支付金额</p>
        </div>
        HTML;

        return $this->content($table);
    }
}
