<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('configs')->insertOrIgnore([
            'group' => 'power',
            'key'   => 'alpha_quota_multiple',
            'value' => '2.5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    public function down(): void
    {
        DB::table('configs')->where('key', 'alpha_quota_multiple')->delete();
    }
};
