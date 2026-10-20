<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('上级id');
            $table->unsignedInteger('child_id')->comment('子用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->unsignedTinyInteger('level')->comment('上级等级');
            $table->unsignedTinyInteger('prev_level')->default(0)->comment('前一级等级');
            $table->unsignedTinyInteger('child_level')->comment('子用户等级');
            $table->unsignedTinyInteger('layer')->default(1)->comment('推荐层');
            $table->unsignedTinyInteger('has_node')->default(0)->comment('上级持节点');
            $table->unsignedTinyInteger('is_same_level')->default(0)->comment('平级奖');
            $table->decimal('order_am', 20, 6)->unsigned()->comment('静态收益额');
            $table->decimal('reward_am', 20, 6)->unsigned()->comment('团队奖金额');
            $table->decimal('reward_rate', 10, 2)->unsigned()->comment('收益率(%)');
            $table->date('sett_date')->comment('结算日期');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('user_id');
            $table->index('child_id');
            $table->index('order_id');
            $table->index('sett_date');
            $table->comment('团队奖励');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_rewards');
    }
};
