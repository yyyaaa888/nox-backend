<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invite_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('上级id');
            $table->unsignedInteger('child_id')->comment('子用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->unsignedTinyInteger('level')->comment('级别');
            $table->unsignedInteger('layer')->default(1)->comment('推荐层');
            $table->unsignedTinyInteger('has_node')->default(0)->comment('上级持节点');
            $table->decimal('order_am', 20, 6)->unsigned()->default(0)->comment('订单金额');
            $table->decimal('reward_am', 20, 6)->unsigned()->default(0)->comment('奖励金额');
            $table->decimal('reward_rate', 10, 2)->unsigned()->default(0)->comment('比例(%)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('user_id');
            $table->index('child_id');
            $table->index('order_id');
            $table->comment('邀请奖励');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_rewards');
    }
};
