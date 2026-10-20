<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('old_level')->comment('原等级');
            $table->unsignedInteger('new_level')->comment('新等级');
            $table->text('remark')->nullable()->comment('备注');
            $table->dateTime('created_at')->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_histories');
    }
};
