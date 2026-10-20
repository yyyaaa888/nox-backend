<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeMenuPermissionsCmd extends Command
{
    protected $signature = 'app:make-menu-permissions';

    protected $description = '创建菜单权限';

    public function handle()
    {
        // 只允许非生产环境执行
        if (app()->environment('production')) {
            $this->error('当前是生产环境，不允许执行');

            return 1;
        }

        $menus = DB::table('admin_menu')
            ->where('id', '>=', 10)
            ->where('show', 1)
            ->orderBy('id', 'asc')
            ->cursor();
        $total = 0;
        foreach ($menus as $menu) {
            $exists = DB::table('admin_permissions')
                ->where('id', $menu->id)
                ->exists();
            if (! $exists) {
                DB::table('admin_permissions')->insert([
                    'id'          => $menu->id,
                    'name'        => $menu->title,
                    'slug'        => $menu->title,
                    'http_method' => null,
                    'http_path'   => empty($menu->uri) ? '' : ('/'.$menu->uri.'*'),
                    'order'       => $menu->order,
                    'parent_id'   => $menu->parent_id,
                    'created_at'  => '2000-01-01 00:00:00',
                    'updated_at'  => '2000-01-01 00:00:00',
                ]);
                $this->info("权限 {$menu->id} 创建成功");
            }
            $exists = DB::table('admin_permission_menu')
                ->where('permission_id', $menu->id)
                ->where('menu_id', $menu->id)
                ->exists();
            if (! $exists) {
                DB::table('admin_permission_menu')->insert([
                    'permission_id' => $menu->id,
                    'menu_id'       => $menu->id,
                    'created_at'    => '2000-01-01 00:00:00',
                    'updated_at'    => '2000-01-01 00:00:00',
                ]);
                $this->info("菜单权限 {$menu->id} 创建成功");
            }
            $total++;
        }

        return 0;
    }
}
