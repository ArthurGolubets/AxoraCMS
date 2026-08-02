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
        DB::statement("ALTER TABLE t_characteristic_definitions MODIFY COLUMN type ENUM('string', 'number', 'boolean', 'color', 'image') DEFAULT 'string'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if any existing records use 'color' or 'image' types
        DB::statement("ALTER TABLE t_characteristic_definitions MODIFY COLUMN type ENUM('string', 'number', 'boolean') DEFAULT 'string'");
    }
};
