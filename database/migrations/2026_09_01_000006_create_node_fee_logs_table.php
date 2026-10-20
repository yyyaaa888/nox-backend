<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_fee_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('alloc_id')->comment('分配记录id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->decimal('am', 20, 6)->unsigned()->comment('该用户分红金额');
            $table->unsignedInteger('node_num')->default(0)->comment('该用户节点数量');
            $table->unsignedInteger('total_num')->default(0)->comment('本次全局节点总数');
            $table->decimal('total_am', 20, 6)->unsigned()->default(0)->comment('分红总金额(池金额)');
            $table->decimal('per_share', 20, 6)->unsigned()->default(0)->comment('每份分红额');
            $table->date('sett_date')->comment('结算日期');
            $table->dateTime('created_at')->nullable();
            $table->unique(['alloc_id', 'user_id']);
            $table->unique(['sett_date', 'user_id']);
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_fee_logs');
    }
};
