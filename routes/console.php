<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

// 获取代币价格: app:get-token-price 每分钟执行一次
Schedule::command('app:get-token-price')->everyMinute()->name('get-token-price')->appendOutputTo(storage_path('logs/get-token-price.log'));

// 支付补单: app:order-pay 每分钟执行一次
Schedule::command('app:order-pay')->everyMinute()->name('order-pay')->appendOutputTo(storage_path('logs/order-pay.log'));

// 提现补单: app:withdraw-scan 每分钟执行一次
Schedule::command('app:withdraw-scan')->everyMinute()->name('withdraw-scan')->appendOutputTo(storage_path('logs/withdraw-scan.log'));

// 资产包结算: app:alpha-reward 每天执行一次
Schedule::command('app:alpha-reward')
    ->withoutOverlapping() // 避免任务重复
    ->runInBackground() // 后台执行
    ->dailyAt('00:20')
    ->name('alpha-reward')
    ->appendOutputTo(storage_path('logs/alpha-reward.log'));

// 节点池分红: app:node-fee 每天执行一次
Schedule::command('app:node-fee')
    ->withoutOverlapping()
    ->runInBackground()
    ->dailyAt('00:30')
    ->name('node-fee')
    ->appendOutputTo(storage_path('logs/node-fee.log'));
