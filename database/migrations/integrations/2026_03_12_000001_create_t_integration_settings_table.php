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
        Schema::create('t_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('integration_type'); // telegram, yookassa
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, array, json
            $table->timestamps();

            $table->unique(['integration_type', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_integration_settings');
    }
};
