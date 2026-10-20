<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedTinyInteger('change_type')->comment('变更类型 1=入 2=冻结 3=出 4=回撤');
            $table->unsignedTinyInteger('type')->comment('变动类型');
            $table->string('symbol', 32)->comment('币种名称');
            $table->decimal('amount', 20, 6)->default('0.000000')->comment('变动金额');
            $table->decimal('before_amount', 20, 6)->default('0.000000')->comment('变动前');
            $table->decimal('after_amount', 20, 6)->default('0.000000')->comment('变动后');
            $table->decimal('before_freeze', 20, 6)->default('0.000000')->comment('变动前(冻结金额)');
            $table->decimal('after_freeze', 20, 6)->default('0.000000')->comment('变动后(冻结金额)');
            $table->dateTime('created_at')->nullable()->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_logs');
    }
};
