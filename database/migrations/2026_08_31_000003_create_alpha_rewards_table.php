<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->decimal('order_am', 20, 6)->unsigned()->comment('订单金额');
            $table->decimal('rate', 10, 2)->unsigned()->comment('月产率(%)');
            $table->decimal('reward_am', 20, 6)->unsigned()->comment('本次产出额');
            $table->decimal('quota_used_am', 20, 6)->unsigned()->default(0)->comment('计入额度槽金额');
            $table->decimal('frozen_am', 20, 6)->unsigned()->default(0)->comment('本次冻结金额');
            $table->date('settle_date')->comment('结算日');
            $table->dateTime('created_at')->nullable();
            $table->unique(['order_id', 'settle_date']);
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_rewards');
    }
};
