<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->unique()->after('name');
            $table->string('avatar')->nullable()->after('email');
            $table->string('pay_password')->nullable()->after('password');
            $table->unsignedTinyInteger('status')->default(1)->after('updated_at');
            $table->unsignedInteger('invited_uid')->default(0)->after('status');
            $table->string('invite_code')->nullable()->unique()->after('invited_uid');
            $table->unsignedInteger('level_id')->default(1)->after('invite_code');
            $table->unsignedInteger('virtual_level')->default(1)->after('level_id');
            $table->unsignedInteger('nft_id')->default(0)->after('virtual_level');
            $table->string('remark')->default('')->after('nft_id');
            $table->unsignedInteger('team_num')->default(0)->after('remark');
            $table->unsignedInteger('direct_num')->default(0)->after('team_num');
            $table->decimal('user_perf', 20, 6)->unsigned()->default('0.000000')->after('direct_num');
            $table->decimal('team_perf', 20, 6)->unsigned()->default('0.000000')->after('user_perf');
            $table->decimal('big_perf', 20, 6)->unsigned()->default('0.000000')->after('team_perf');
            $table->decimal('small_perf', 20, 6)->unsigned()->default('0.000000')->after('big_perf');
            $table->dateTime('perf_at')->nullable()->after('small_perf');
            $table->unsignedTinyInteger('allow_w_usdt')->default(1)->after('perf_at');
            $table->unsignedTinyInteger('allow_w_nox')->default(1)->after('allow_w_usdt');

            $table->index('invited_uid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['invited_uid']);
            $table->dropIndex(['place_pid']);
            $table->dropUnique(['address']);
            $table->dropUnique(['invite_code']);
            $table->dropColumn([
                'address',
                'avatar',
                'pay_password',
                'status',
                'invited_uid',
                'invite_code',
                'level_id',
                'virtual_level',
                'nft_id',
                'remark',
                'team_num',
                'direct_num',
                'team_perf',
                'big_perf',
                'small_perf',
                'perf_at',
                'allow_w_usdt',
                'allow_w_nox',
            ]);
        });
    }
};
