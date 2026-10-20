<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserUpgradeService;
use Illuminate\Console\Command;

class CheckUserLevelsCmd extends Command
{
    protected $signature = 'app:check-levels';

    protected $description = '自动检测用户等级并升级';

    public function handle()
    {
        ini_set('memory_limit', '512M'); // 设置内存限制
        $this->info('开始检查用户等级...');

        $users   = User::orderBy('id', 'desc')->cursor();
        $service = new UserUpgradeService(true);
        foreach ($users as $user) {
            $service->check($user->id);
        }

        $this->info('等级检查完成');

        return 0;
    }
}
