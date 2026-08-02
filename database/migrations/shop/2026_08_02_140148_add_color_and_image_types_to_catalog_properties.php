<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Изменяем тип поля type, добавляя новые значения 'color' и 'image'
        DB::statement("ALTER TABLE t_catalog_properties MODIFY COLUMN type ENUM('string', 'text', 'number', 'color', 'image') DEFAULT 'string'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем предыдущие значения enum
        DB::statement("ALTER TABLE t_catalog_properties MODIFY COLUMN type ENUM('string', 'text', 'number') DEFAULT 'string'");
    }
};
