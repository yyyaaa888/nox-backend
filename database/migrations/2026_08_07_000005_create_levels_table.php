<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 30)->comment('编号');
            $table->unsignedInteger('user_perf')->comment('个人业绩');
            $table->unsignedInteger('team_perf')->comment('团队业绩');
            $table->unsignedInteger('direct_num')->comment('直推人数');
            $table->unsignedInteger('team_num')->comment('团队人数');
            $table->decimal('weekly_limit_usdt', 20, 6)->unsigned()->default(0)->comment('周USDT提现上限');
            $table->decimal('weekly_limit_nox', 20, 6)->unsigned()->default(0)->comment('周NOX提现上限');
            $table->dateTime('created_at')->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->unique('code', 'code_idx');
            $table->comment('用户等级');
        });

        $config = [
            // id => [code, user_perf, team_perf, direct_num, team_num, weekly_limit_usdt, weekly_limit_nox]
            1 => ['V1', 0, 0, 0, 0, '300.000000', '300.000000'],
            2 => ['V2', 500, 5000, 3, 10, '500.000000', '500.000000'],
            3 => ['V3', 3000, 30000, 5 ,10, '1000.000000', '1000.000000'],
            4 => ['V4', 30000, 500000, 10, 50, '3000.000000', '3000.000000'],
            5 => ['V5', 0, 1000000, 20, 100, '5000.000000', '5000.000000'],
        ];
        $rows = [];
        foreach ($config as $id => [$code, $user_perf, $team_perf, $direct_num, $team_num, $usdt, $nox]) {
            $rows[] = [
                'id' => $id,
                'code' => $code,
                'user_perf' => $user_perf,
                'team_perf' => $team_perf,
                'direct_num' => $direct_num,
                'team_num' => $team_num,
                'weekly_limit_usdt' => $usdt,
                'weekly_limit_nox' => $nox,
                'created_at' => '2000-01-01 00:00:00',
                'updated_at' => '2000-01-01 00:00:00',
            ];
        }
        DB::table('levels')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
