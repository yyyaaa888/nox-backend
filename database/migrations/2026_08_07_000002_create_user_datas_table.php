<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('node_id')->default(0)->comment('节点id');
            $table->unsignedInteger('node_num')->default(0)->comment('节点数量');
            $table->decimal('total_stake', 20, 6)->unsigned()->default('0.000000')->comment('累计质押');
            $table->decimal('curr_stake', 20, 6)->unsigned()->default('0.000000')->comment('当前质押');
            $table->decimal('total_power', 20, 6)->unsigned()->default('0.000000')->comment('累计算力');
            $table->decimal('curr_power', 20, 6)->unsigned()->default('0.000000')->comment('当前算力');
            $table->decimal('perf', 20, 6)->unsigned()->default('0.000000')->comment('业绩');
            $table->decimal('total_usdt', 20, 6)->unsigned()->default('0.000000')->comment('累计USDT');
            $table->decimal('current_usdt', 20, 6)->unsigned()->default('0.000000')->comment('当前USDT');
            $table->decimal('freeze_usdt', 20, 6)->unsigned()->default('0.000000')->comment('冻结USDT');
            $table->decimal('total_nox', 20, 6)->unsigned()->default('0.000000')->comment('累计NOX');
            $table->decimal('current_nox', 20, 6)->unsigned()->default('0.000000')->comment('当前NOX');
            $table->decimal('freeze_nox', 20, 6)->unsigned()->default('0.000000')->comment('冻结NOX');
            $table->decimal('node_reward', 20, 6)->unsigned()->default('0.000000')->comment('节点收益');
            $table->decimal('stake_reward', 20, 6)->unsigned()->default('0.000000')->comment('质押收益');
            $table->decimal('invite_reward', 20, 6)->unsigned()->default('0.000000')->comment('邀请奖励');
            $table->decimal('team_reward', 20, 6)->unsigned()->default('0.000000')->comment('团队奖励');
            $table->decimal('fee_fh', 20, 6)->unsigned()->default('0.000000')->comment('手续费分红');
            $table->dateTime('node_at')->nullable()->comment('节点持有时间');
            $table->dateTime('created_at')->nullable()->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_datas');
    }
};
