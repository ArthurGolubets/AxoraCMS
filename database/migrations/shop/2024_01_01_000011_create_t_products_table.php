<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('t_catalogs')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('sku')->unique();
            $table->json('tags')->nullable();
            $table->boolean('is_new')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true);
            $table->longText('content')->nullable();
            $table->json('gallery')->nullable();
            $table->timestamps();

            $table->index('catalog_id');
            $table->index('slug');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_products');
    }
};
