<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->string('key')->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('group')->comment('配置组');
            $table->dateTime('created_at')->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->primary('key');
            $table->comment('参数设置表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
