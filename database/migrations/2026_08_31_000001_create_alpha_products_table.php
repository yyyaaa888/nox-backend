<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alpha_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('产品名称');
            $table->string('tier', 20)->comment('档位 basic/high');
            $table->decimal('min_amount', 20, 6)->unsigned()->default(0)->comment('最小申购额');
            $table->decimal('max_amount', 20, 6)->unsigned()->default(0)->comment('最大申购额');
            $table->decimal('per_power', 20, 6)->unsigned()->default(0)->comment('每单位算力值');
            $table->decimal('min_rate', 10, 2)->unsigned()->default(0)->comment('最低月产率(%)');
            $table->decimal('max_rate', 10, 2)->unsigned()->default(0)->comment('最高月产率(%)');
            $table->decimal('base_rate', 10, 2)->unsigned()->comment('基础月产率(%)');
            $table->decimal('node_rate', 10, 2)->unsigned()->default(0)->comment('持节点加速月产率(%)');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=启用 0=停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('alpha_products');
    }
};
