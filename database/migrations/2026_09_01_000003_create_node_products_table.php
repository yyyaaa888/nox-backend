<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('节点名称');
            $table->decimal('price', 20, 6)->unsigned()->comment('价格');
            $table->unsignedTinyInteger('status')->default(1)->comment('1=启用 0=停用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('node_products');
    }
};
