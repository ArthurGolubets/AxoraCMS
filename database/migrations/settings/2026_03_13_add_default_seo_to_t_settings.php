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
        // Add default SEO settings
        DB::table('t_panel_settings')->insert([
            ['key' => 'default_meta_title', 'value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_meta_description', 'value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_meta_keywords', 'value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('t_panel_settings')
            ->whereIn('key', ['default_meta_title', 'default_meta_description', 'default_meta_keywords'])
            ->delete();
    }
};
