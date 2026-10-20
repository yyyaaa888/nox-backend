<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_datas', function (Blueprint $table) {
            $table->decimal('current_slot', 20, 6)->unsigned()->default(0)->comment('额度槽');
            $table->decimal('alpha_quota_used', 20, 6)->unsigned()->default(0)->comment('额度槽已用(C)');
            $table->decimal('alpha_frozen', 20, 6)->unsigned()->default(0)->comment('冻结收益');
        });
    }
    public function down(): void
    {
        Schema::table('user_datas', function (Blueprint $table) {
            $table->dropColumn(['current_slot', 'alpha_quota_used', 'alpha_frozen']);
        });
    }
};
