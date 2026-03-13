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
        // Add header_menu_id setting
        DB::table('t_panel_settings')->insert([
            'key' => 'header_menu_id',
            'value' => null,
            'type' => 'integer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add footer_menu_id setting
        DB::table('t_panel_settings')->insert([
            'key' => 'footer_menu_id',
            'value' => null,
            'type' => 'integer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('t_panel_settings')->whereIn('key', ['header_menu_id', 'footer_menu_id'])->delete();
    }
};
