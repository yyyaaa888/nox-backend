<?php

namespace App\Console\Commands;

use App\Models\BuyCycle;
use App\Models\BuyOrder;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestNewBuyOrderCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:new-buy-order {user_id} {--days=45} {--num=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 只允许非生产环境执行
        if (app()->environment('production')) {
            $this->error('当前是生产环境，不允许执行');

            return;
        }

        // 设为昨日
        Carbon::setTestNow(Carbon::now()->subDay());

        $user_id = $this->argument('user_id');
        $user    = User::find($user_id);
        if (! $user) {
            $this->error('用户不存在');

            return;
        }

        $days  = $this->option('days');
        $cycle = BuyCycle::where('days', $days)->first();
        if (! $cycle) {
            $this->error('周期不存在');

            return;
        }

        $num    = $this->option('num');
        $price  = 1;
        $amount = $price * $num;

        $now = Carbon::now();
        // 创建订单
        $order                   = new BuyOrder();
        $order->sn               = randOrderNo();
        $order->user_id          = $user->id;
        $order->days             = $cycle->days;
        $order->price            = $price; // 代币价格
        $order->num              = $num; // 代币数量
        $order->amount           = $amount; // 订单金额
        $order->json             = 'null';
        $order->pay_address      = $user->address;
        $order->contract_address = '0x0000000000000000000000000000000000000000';
        $order->wallet_address   = '0x0000000000000000000000000000000000000000';
        $order->status           = BuyOrder::STATUS_STAKING;
        $order->pay_status       = BuyOrder::PAY_STATUS_PAID;
        $order->start_time       = $now;
        $order->end_time         = $now->copy()->addDays($cycle->days)->endOfDay();

        $order->hash       = null;
        $order->pay_at     = $now;
        $order->pay_amount = $amount;
        $order->save();

        UserData::where('user_id', $order->user_id)->update([
            'total_buy'       => DB::raw('total_buy + '.$order->num),
            'total_buy_usdt'  => DB::raw('total_buy_usdt + '.$order->amount),
            'total_staking'   => DB::raw('total_staking + '.$order->num),
            'current_staking' => DB::raw('current_staking + '.$order->num),
        ]);

        $this->info('sn: '.$order->sn);
        $this->info('num: '.$num);
        $this->info('days: '.$days);
    }
}
