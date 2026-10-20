<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 10)->comment('invite/team');
            $table->unsignedTinyInteger('rank')->comment('V等级');
            $table->unsignedTinyInteger('has_node')->comment('0=基础 1=持节点');
            $table->unsignedTinyInteger('layer')->comment('推荐层');
            $table->decimal('rate', 10, 2)->unsigned()->comment('奖励率(%)');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['type', 'rank', 'has_node', 'layer']);
        });

        // 既定制度种子
        $rates = [
            'invite' => [
                1 => [0 => ['2.00', '1.00', '1.00'], 1 => ['3.00', '2.00', '1.00']],
                2 => [0 => ['5.00', '2.00', '1.00'], 1 => ['7.00', '3.00', '1.00']],
                3 => [0 => ['7.00', '2.00', '1.00', '1.00'], 1 => ['10.00', '3.00', '1.00', '1.00']],
                4 => [0 => ['7.00', '4.00', '2.00', '1.00'], 1 => ['10.00', '5.00', '3.00', '1.00']],
                5 => [0 => ['8.00', '4.00', '2.00', '2.00', '2.00'], 1 => ['12.00', '5.00', '3.00', '3.00', '3.00']],
            ],
            'team' => [
                1 => [0 => ['2.00', '1.00', '1.00'], 1 => ['3.00', '1.00', '1.00']],
                2 => [0 => ['4.00', '2.00', '1.00'], 1 => ['5.00', '3.00', '1.00']],
                3 => [0 => ['5.00', '2.00', '2.00', '1.00'], 1 => ['7.00', '3.00', '3.00', '1.00']],
                4 => [0 => ['7.00', '4.00', '2.00', '2.00'], 1 => ['10.00', '5.00', '3.00', '3.00']],
                5 => [0 => ['7.00', '5.00', '4.00', '2.00', '2.00'], 1 => ['10.00', '7.00', '5.00', '3.00', '3.00']],
            ],
        ];
        $now = now();
        foreach ($rates as $type => $byRank) {
            foreach ($byRank as $rank => $byNode) {
                foreach ($byNode as $hasNode => $layers) {
                    foreach ($layers as $i => $rate) {
                        DB::table('reward_rates')->insert([
                            'type' => $type, 'rank' => $rank, 'has_node' => $hasNode,
                            'layer' => $i + 1, 'rate' => $rate,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_rates');
    }
};
