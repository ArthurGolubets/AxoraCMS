<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_catalog_property_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('t_catalogs')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('sort_order')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_catalog_property_groups');
    }
};
