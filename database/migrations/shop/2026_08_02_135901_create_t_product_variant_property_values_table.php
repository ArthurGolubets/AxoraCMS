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
        Schema::create('t_product_variant_property_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('t_product_variants')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('t_catalog_properties')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['variant_id', 'property_id']);
            $table->index('variant_id');
            $table->index('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_product_variant_property_values');
    }
};
