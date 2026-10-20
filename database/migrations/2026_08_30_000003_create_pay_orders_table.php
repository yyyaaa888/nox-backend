<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->string('sn', 100)->comment('支付单号');
            $table->unsignedInteger('order_id')->comment('订单id');
            $table->unsignedTinyInteger('type')->comment('类型');
            $table->decimal('amount', 16, 6)->comment('总金额');
            $table->decimal('balance', 16, 6)->default(0)->comment('余额支付');
            $table->unsignedTinyInteger('decimals')->comment('金额小数位');
            $table->unsignedTinyInteger('status')->comment('支付状态 0=待支付 1=已支付 2=支付取消 3=支付失败 4=支付超时');
            $table->decimal('pay_total', 16, 6)->nullable()->comment('支付金额');
            $table->string('symbol', 100)->nullable()->comment('资产类型');
            $table->string('hash', 100)->nullable()->comment('交易哈希');
            $table->string('pay_contract', 100)->nullable()->comment('支付合约');
            $table->string('pay_addr', 100)->nullable()->comment('付款地址');
            $table->string('token_addr', 100)->nullable()->comment('代币地址');
            $table->string('dest_addr', 100)->nullable()->comment('收款地址');
            $table->text('fail_reason')->nullable()->comment('失败原因');
            $table->unsignedInteger('try_num')->default(0)->comment('重试次数');
            $table->dateTime('last_time')->nullable()->comment('超时时间');
            $table->dateTime('pay_at')->nullable()->comment('成功时间');
            $table->dateTime('fail_at')->nullable()->comment('失败或超时时间');
            $table->timestamps();

            $table->unique('sn');
            $table->unique('hash');
            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_orders');
    }
};
