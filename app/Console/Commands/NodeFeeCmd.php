<?php

namespace App\Console\Commands;

use App\Services\NodeFeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NodeFeeCmd extends Command
{
    protected $signature = 'app:node-fee {date?} {--skip}';

    protected $description = '节点池每日分红结算';

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
            $service = new NodeFeeService();

            return $service->run($inputDate, $skip ? true : false) ? 0 : 1;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }
}
