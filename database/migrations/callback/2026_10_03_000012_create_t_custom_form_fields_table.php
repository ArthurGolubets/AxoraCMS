<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_custom_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('t_custom_forms')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            // string | text | email | phone | number | date | bool | enum | image | file | entity | user
            $table->string('type', 20)->default('string');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_multiple')->default(false);
            $table->unsignedInteger('sort')->default(500);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_custom_form_fields');
    }
};
