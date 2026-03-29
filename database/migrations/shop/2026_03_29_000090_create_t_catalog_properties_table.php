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
        Schema::create('t_catalog_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('t_catalogs')->onDelete('cascade');
            $table->string('code')->comment('Property code');
            $table->string('name')->comment('Property name');
            $table->enum('type', ['string', 'text', 'number'])->default('string');
            $table->boolean('is_multiple')->default(false)->comment('Can have multiple values');
            $table->integer('sort_order')->default(500);
            $table->timestamps();

            $table->unique(['catalog_id', 'code']);
            $table->index('catalog_id');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_catalog_properties');
    }
};
