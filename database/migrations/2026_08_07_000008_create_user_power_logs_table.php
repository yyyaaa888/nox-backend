<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CREATE TABLE `user_power_logs` (
//     `id`      int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `user_id` int(10) unsigned NOT NULL COMMENT '用户id',
//     `type`          tinyint(4) NOT NULL COMMENT '类型',
//     `change_type`   tinyint(4) NOT NULL COMMENT '变动类型 1=增加 2=减少',
//     `amount`        decimal(20,6) unsigned NOT NULL DEFAULT '0' COMMENT '变动金额',
//     `before_amount` decimal(20,6) unsigned NOT NULL DEFAULT '0' COMMENT '变动前',
//     `after_amount`  decimal(20,6) unsigned NOT NULL DEFAULT '0' COMMENT '变动后',
//     `remark`        varchar(255)           NOT NULL DEFAULT ''  COMMENT '备注',
//     `created_at`    datetime NOT NULL     COMMENT '添加时间',
//     `updated_at`    datetime DEFAULT NULL COMMENT '更新时间',
//     PRIMARY KEY (`id`),
//     KEY (`user_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='算力日志';

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_power_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedTinyInteger('type')->comment('类型');
            $table->unsignedTinyInteger('change_type')->comment('变动类型 1=增加 2=减少');
            $table->decimal('amount', 20, 6)->unsigned()->default('0.000000')->comment('变动金额');
            $table->decimal('before_amount', 20, 6)->unsigned()->default('0.000000')->comment('变动前');
            $table->decimal('after_amount', 20, 6)->unsigned()->default('0.000000')->comment('变动后');
            $table->decimal('curr_power', 20, 6)->unsigned()->default('0.000000')->comment('当前算力');
            $table->string('remark', 255)->default('')->comment('备注');
            $table->dateTime('created_at')->comment('添加时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            // $table->index('user_id', 'user_id_idx');
            $table->charset('utf8mb4');
            $table->comment('算力日志');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_power_logs');
    }
};
