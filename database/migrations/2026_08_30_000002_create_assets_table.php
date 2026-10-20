<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('chain_id')->default(0)->comment('区块链id');
            $table->string('name', 50)->comment('币种单位');
            $table->string('title', 50)->comment('币种标题');
            $table->string('code', 32)->comment('币种CODE');
            $table->unsignedTinyInteger('decimals')->default(18)->comment('精度');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1=主币 2=代币');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0=禁用  1=启用');
            $table->tinyInteger('is_exchange')->nullable()->default(1)->comment('是否开启兑换');
            $table->decimal('exchange_rate', 19, 8)->nullable()->default(0)->comment('汇率');
            $table->text('contract_abi')->nullable()->comment('币种的token_abi');
            $table->string('contract_address', 100)->nullable()->comment('代币合约地址');
            $table->string('withdraw_address', 100)->nullable()->comment('提现钱包地址');
            $table->string('withdraw_key', 1000)->nullable()->comment('提现钱包加密私钥');
            $table->string('wallet_address', 100)->nullable()->comment('收款钱包地址');
            $table->datetimes();
            $table->index('chain_id');
            $table->index('code');
            $table->comment('系统币种表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
