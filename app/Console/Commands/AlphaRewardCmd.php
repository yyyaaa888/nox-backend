<?php

namespace App\Console\Commands;

use App\Services\AlphaRewardService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AlphaRewardCmd extends Command
{
    protected $signature = 'app:alpha-reward {date?} {--skip}';

    protected $description = '资产包每日产出结算';

    public function handle()
    {
        $inputDate = $this->argument('date');
        $skip      = $this->option('skip');
        if (! $inputDate) {
            $inputDate = Carbon::yesterday()->toDateString();
        }
        $date = Carbon::createFromFormat('Y-m-d', $inputDate);
        if (! $date || $date->format('Y-m-d') !== $inputDate) {
            $this->error('date format must be Y-m-d');

            return 1;
        }
        try {
            $service = new AlphaRewardService();

            return $service->run($inputDate, $skip ? true : false) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }
}
