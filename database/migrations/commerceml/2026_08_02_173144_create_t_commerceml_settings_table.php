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
        Schema::create('t_commerceml_settings', function (Blueprint $table) {
            $table->id();
            $table->string('login')->nullable();
            $table->string('password')->nullable();
            $table->enum('import_type', ['separate', 'monolith'])->default('separate');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_commerceml_settings');
    }
};
