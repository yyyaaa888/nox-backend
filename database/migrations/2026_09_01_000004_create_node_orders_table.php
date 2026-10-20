<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->unsignedInteger('product_id')->comment('产品id');
            $table->string('sn', 20)->comment('单号');
            $table->unsignedInteger('num')->comment('数量');
            $table->decimal('price', 20, 6)->unsigned()->comment('单价');
            $table->decimal('amount', 20, 6)->unsigned()->comment('本金');
            $table->unsignedTinyInteger('status')->default(0)->comment('0=待支付 1=持有中 2=取消');
            $table->dateTime('pay_at')->nullable();
            $table->dateTime('fail_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->index('user_id');
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_orders');
    }
};
