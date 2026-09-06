<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curated "companion products" (сопутствующие товары / наборы):
 * for a product — optionally scoped to one of its variants — a list of
 * other products (optionally a specific variant) that form a set.
 *
 * Variants are identified by SKU rather than id: the shop stores variants
 * "delete-all + recreate from payload" on every product save, so ids are not
 * stable but the SKU coming back in the payload is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_product_related', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('variant_sku', 191)->nullable();
            $table->unsignedBigInteger('related_product_id');
            $table->string('related_variant_sku', 191)->nullable();
            $table->unsignedInteger('sort')->default(500);
            $table->timestamps();

            $table->index('product_id');
            $table->index('related_product_id');
            $table->unique(
                ['product_id', 'variant_sku', 'related_product_id', 'related_variant_sku'],
                't_product_related_unique'
            );

            $table->foreign('product_id')->references('id')->on('t_products')->cascadeOnDelete();
            $table->foreign('related_product_id')->references('id')->on('t_products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_product_related');
    }
};
