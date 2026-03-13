<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add social_links setting
        DB::table('t_panel_settings')->insert([
            'key' => 'social_links',
            'value' => '[]',
            'type' => 'json',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('t_panel_settings')->where('key', 'social_links')->delete();
    }
};
