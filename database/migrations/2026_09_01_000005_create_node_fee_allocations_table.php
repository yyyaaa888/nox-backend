<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_fee_allocations', function (Blueprint $table) {
            $table->increments('id');
            $table->date('sett_date')->comment('结算日');
            $table->decimal('total_fee', 20, 6)->unsigned()->default(0)->comment('当日手续费总额');
            $table->decimal('node_fee_am', 20, 6)->unsigned()->default(0)->comment('进节点池金额');
            $table->unsignedInteger('node_count')->default(0)->comment('活跃节点份数');
            $table->decimal('per_share', 20, 6)->unsigned()->default(0)->comment('每份分红额');
            $table->decimal('project_am', 20, 6)->unsigned()->default(0)->comment('项目收入');
            $table->decimal('remain', 20, 6)->unsigned()->default(0)->comment('未分配余量');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=进行中 2=已结束 3=失败');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sett_date');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_fee_allocations');
    }
};
