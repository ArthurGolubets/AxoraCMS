<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-defined custom project fields ("Пользовательские свойства").
 *
 * The panel is a singleton, so each row carries both the field definition
 * and its value. Values surface on the frontend through projectSettings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_panel_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            // text | html | number | image | file | email | phone | table
            $table->string('type', 20)->default('text');
            $table->boolean('is_multiple')->default(false);
            $table->unsignedInteger('sort')->default(500);
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_panel_custom_fields');
    }
};
