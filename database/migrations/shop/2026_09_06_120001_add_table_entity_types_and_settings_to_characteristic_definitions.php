<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add "table" and "entity" to the characteristic definition type enum
        // (color / image were added by an earlier migration).
        DB::statement("ALTER TABLE t_characteristic_definitions MODIFY COLUMN type ENUM('string', 'number', 'boolean', 'color', 'image', 'table', 'entity') DEFAULT 'string'");

        // Per-characteristic configuration (allowed entity types, locked infoblock, table defaults, ...).
        if (! Schema::hasColumn('t_characteristic_definitions', 'settings')) {
            Schema::table('t_characteristic_definitions', function (Blueprint $table) {
                $table->json('settings')->nullable()->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('t_characteristic_definitions', 'settings')) {
            Schema::table('t_characteristic_definitions', function (Blueprint $table) {
                $table->dropColumn('settings');
            });
        }

        DB::statement("UPDATE t_characteristic_definitions SET type = 'string' WHERE type IN ('table', 'entity')");
        DB::statement("ALTER TABLE t_characteristic_definitions MODIFY COLUMN type ENUM('string', 'number', 'boolean', 'color', 'image') DEFAULT 'string'");
    }
};
