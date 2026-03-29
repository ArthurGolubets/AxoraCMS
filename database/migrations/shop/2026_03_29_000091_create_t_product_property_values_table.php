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
        Schema::create('t_product_property_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('t_products')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('t_catalog_properties')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'property_id']);
            $table->index('product_id');
            $table->index('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_product_property_values');
    }
};
