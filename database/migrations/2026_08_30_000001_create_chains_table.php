<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->nullable()->comment('链名称');
            $table->string('alias', 50)->nullable()->comment('别名');
            $table->integer('chain_id')->nullable()->comment('链id');
            $table->string('symbol', 10)->nullable()->comment('符号');
            $table->tinyInteger('decimals')->default(0)->comment('精度');
            $table->string('rpc_url', 255)->nullable()->comment('rpc地址');
            $table->string('scan_url', 255)->nullable()->comment('区块浏览器地址');
            $table->tinyInteger('status')->default(1)->comment('是否开启');
            $table->datetimes();
            $table->softDeletesDatetime();
            $table->unique('chain_id');
            $table->comment('区块链列表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chains');
    }
};
