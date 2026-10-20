<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('amount', 20, 6)->unsigned()->comment('金额');
            $table->date('date')->comment('日期');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('date');
            $table->comment('手续费表');
        });
        Schema::create('fee_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedTinyInteger('type')->comment('事件类型');
            $table->decimal('amount', 20, 6)->unsigned()->comment('金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->dateTime('created_at')->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->index('user_id');
            $table->comment('手续费日志');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('fees');
        Schema::dropIfExists('fee_logs');
    }
};
