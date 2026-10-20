<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_relation_closures', function (Blueprint $table) {
            $table->integer('ancestor_id')->comment('祖先id');
            $table->integer('descendant_id')->comment('用户id');
            $table->integer('depth')->comment('层级');

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth'], 'descendant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_relation_closures');
    }
};
