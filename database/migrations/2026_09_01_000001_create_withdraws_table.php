<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->comment('用户id');
            $table->string('sn', 255)->comment('单号');
            $table->string('symbol', 100)->comment('账户类型');
            $table->decimal('amount', 20, 6)->default(0)->comment('金额');
            $table->decimal('real_am', 20, 6)->default(0)->comment('到手金额');
            $table->decimal('fee', 20, 6)->default(0)->comment('手续费');
            $table->decimal('fee_rate', 20, 2)->default(0)->comment('手续费率');
            $table->decimal('back_am', 20, 6)->default(0)->comment('已返还金额');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=待审核 2=转帐中 3=已完成 4=失败 5=已驳回');
            $table->unsignedTinyInteger('withdraw_mode')->default(1)->comment('1=审核 2=自动 3=合约');
            $table->string('to_addr', 255)->nullable()->comment('用户钱包地址');
            $table->string('signature', 255)->nullable()->comment('签名');
            $table->string('message', 255)->nullable()->comment('消息');
            $table->string('withdraw_contract', 255)->nullable()->comment('提现合约');
            $table->string('project_signer', 255)->nullable()->comment('项目方');
            $table->dateTime('deadline')->nullable()->comment('签名有效时间');
            $table->unsignedInteger('try_num')->default(0)->comment('重试次数');
            $table->unsignedTinyInteger('is_back')->default(0)->comment('是否已返还');
            $table->string('hash', 255)->nullable()->comment('交易凭据');
            $table->dateTime('audit_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->dateTime('fail_at')->nullable();
            $table->dateTime('back_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('sn');
            $table->unique('hash');
            $table->index('user_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
