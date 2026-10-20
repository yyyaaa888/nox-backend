<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TestNewUserCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:new-user {address} {--invite_code=88888888}';

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

        $address = $this->argument('address');
        $address = strtolower($address);
        $exist   = User::where('address', $address)->exists();
        if ($exist) {
            $this->error('地址已存在');

            return;
        }

        $invite_code = $this->option('invite_code');
        if (empty($invite_code)) {
            $this->error('邀请码不能为空');

            return;
        }
        $inviteInfo = User::where('invite_code', $invite_code)->first();
        if (! $inviteInfo) {
            $this->error('邀请码不存在');

            return;
        }
        $pid = $inviteInfo->id;

        $user = UserService::registerNewUser($address, $pid);

        $this->info('id: '.$user['id']);
        $this->info('name: '.$user['name']);
        $this->info('address: '.$user['address']);
        $this->info('invite_code: '.$user['invite_code']);
        $this->info('pid: '.$user['invited_uid']);
        $this->info('token: '.$user['token']);
    }
}
