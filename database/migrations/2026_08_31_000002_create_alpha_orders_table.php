<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('product_id')->comment('产品id');
            $table->string('sn', 20)->comment('单号');
            $table->decimal('amount', 20, 6)->unsigned()->comment('本金');
            $table->decimal('power_am', 20, 6)->unsigned()->comment('算力值');
            $table->decimal('token_price', 20, 8)->unsigned()->default(0)->comment('代币汇率');
            $table->decimal('reward_rate', 10, 2)->unsigned()->comment('月产率快照(%)');
            $table->decimal('quota_reward', 20, 6)->unsigned()->comment('静态收益封顶=2.5*本金');
            $table->decimal('rem_reward', 20, 6)->unsigned()->default(0)->comment('剩余可产出收益');
            $table->decimal('rel_reward', 20, 6)->unsigned()->default(0)->comment('累计已产出');
            $table->decimal('last_reward', 20, 6)->unsigned()->default(0)->comment('最近产出');
            $table->date('last_day')->nullable()->comment('最近产出日期');
            $table->dateTime('trial_end')->nullable()->comment('首月试用截止');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0=待支付 1=进行中 2=已完成 3=已退出 4=取消');
            $table->dateTime('pay_at')->nullable()->comment('支付时间');
            $table->dateTime('done_at')->nullable()->comment('完成时间');
            $table->dateTime('exit_at')->nullable()->comment('退出时间');
            $table->dateTime('fail_at')->nullable()->comment('取消/失败时间');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->index('user_id');
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_orders');
    }
};
