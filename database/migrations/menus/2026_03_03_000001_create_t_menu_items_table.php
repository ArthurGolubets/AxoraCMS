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
        Schema::create('t_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('t_menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('t_menu_items')->onDelete('cascade');
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('route')->nullable();
            $table->enum('target', ['_self', '_blank'])->default('_self');
            $table->integer('sort')->default(500);
            $table->boolean('is_active')->default(true);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();

            $table->index('menu_id');
            $table->index('parent_id');
            $table->index('sort');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_menu_items');
    }
};
