<?php

namespace App\Console\Commands;

use App\Models\Config;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfigSortCmd extends Command
{
    protected $signature = 'app:config-sort';

    protected $description = '排序配置';

    public function handle()
    {
        // 只允许非生产环境执行
        if (app()->environment('production')) {
            $this->error('当前是生产环境，不允许执行');

            return 0;
        }

        DB::table('configs2')->truncate();
        $sysConfigs = Config::where('group', 'system')->orderBy('key', 'asc')->get();
        $idx        = 0;
        foreach ($sysConfigs as $config) {
            $idx++;
            DB::table('configs2')->insert([
                'id'         => $idx,
                'group'      => $config->group,
                'key'        => $config->key,
                'value'      => $config->value,
                'created_at' => '2000-01-01 00:00:00',
                'updated_at' => '2000-01-01 00:00:00',
            ]);
        }

        $configs = Config::where('group', '!=', 'system')->orderBy('group', 'asc')->orderBy('key', 'asc')->cursor();
        $idx += 20 - 1 - ($idx % 10);
        $group = null;
        foreach ($configs as $config) {
            if (! $config->group) {
                $this->error("配置{$config->key}没有分组");

                return 1;
            }
            if ($group && $config->group != $group) {
                $idx += 20 - 1 - ($idx % 10);
            }
            $group = $config->group;
            $idx++;
            DB::table('configs2')->insert([
                'id'         => $idx,
                'group'      => $config->group,
                'key'        => $config->key,
                'value'      => $config->value,
                'created_at' => '2000-01-01 00:00:00',
                'updated_at' => '2000-01-01 00:00:00',
            ]);
        }

        $this->info('排序完成');

        return 0;
    }
}
