<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_product_variants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('attributes');
            $table->text('description')->nullable()->after('image');
            $table->json('addition_info')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_product_variants', function (Blueprint $table) {
            $table->dropColumn(['image', 'description', 'addition_info']);
        });
    }
};
