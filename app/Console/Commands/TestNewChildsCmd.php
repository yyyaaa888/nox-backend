<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TestNewChildsCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:new-childs {address} {--number=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试批量创建子用户';

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

        $parent = User::query()->where('address', $address)->first();
        if (! $parent) {
            $this->error('父用户不存在');

            return;
        }

        $number = (int) $this->option('number');
        if (empty($number)) {
            $this->error('子用户数量不能为空');

            return;
        }

        $idx = 0;
        while ($idx < $number) {
            // 生成子用户地址
            $child_address = generateEthereumAddress();
            $child_address = strtolower($child_address);
            $exists        = User::query()->where('address', $child_address)->exists();
            if ($exists) {
                continue;
            }
            $user = UserService::registerNewUser($child_address, $parent->id);
            $this->info($idx.': '.$user['address']);
            $idx++;
        }
    }
}
