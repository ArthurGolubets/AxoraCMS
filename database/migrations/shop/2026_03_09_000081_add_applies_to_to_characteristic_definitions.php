<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_characteristic_definitions', function (Blueprint $table) {
            $table->enum('applies_to', ['catalog', 'product', 'both'])->default('product')->after('multiple');
        });
    }

    public function down(): void
    {
        Schema::table('t_characteristic_definitions', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};
