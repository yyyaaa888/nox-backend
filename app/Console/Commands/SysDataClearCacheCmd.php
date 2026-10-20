<?php

namespace App\Console\Commands;

use App\SysData;
use Illuminate\Console\Command;

class SysDataClearCacheCmd extends Command
{
    protected $signature = 'sysdata:clear-cache';

    protected $description = '清除数据缓存';

    public function handle()
    {
        SysData::clearCache();
        $this->info('清除数据缓存成功');
    }
}
