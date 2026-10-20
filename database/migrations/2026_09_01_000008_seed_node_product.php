<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('node_products')->insertOrIgnore([
            'name' => '算力加速节点',
            'price' => '1000.000000',
            'status' => 1,
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('node_products')->where('name', '算力加速节点')->delete();
    }
};
