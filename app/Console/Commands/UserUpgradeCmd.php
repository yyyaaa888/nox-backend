<?php

namespace App\Console\Commands;

use App\Services\UserUpgradeService;
use Illuminate\Console\Command;

class UserUpgradeCmd extends Command
{
    protected $signature = 'app:user-upgrade {uid}';

    protected $description = '用户升级检查';

    public function handle()
    {
        $uid = (int) $this->argument('uid');
        if (! $uid) {
            $this->error('uid is required');

            return 1;
        }
        $this->info('开始检查用户等级...');

        $service = new UserUpgradeService(true);
        try {
            $pids = $service->run($uid);
        } catch (\Exception $e) {
            $this->error($e->getMessage().' file:'.$e->getFile().'line:'.$e->getLine());

            return 1;
        }
        $this->info('用户等级检查完成');
        $this->info('pids:'.implode(',', $pids));

        return 0;
    }
}
