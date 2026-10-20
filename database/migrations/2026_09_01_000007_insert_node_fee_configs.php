<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            'group' => 'node',
            'key'   => 'alpha_node_fee_rate',
            'value' => '90',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('configs')->whereIn('key', ['alpha_node_fee_rate'])->delete();
    }
};
