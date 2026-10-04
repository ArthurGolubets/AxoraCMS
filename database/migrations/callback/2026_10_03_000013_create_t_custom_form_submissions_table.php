<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_custom_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('t_custom_forms')->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('administrator_id')->nullable()->comment('Создано администратором');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'viewed_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_custom_form_submissions');
    }
};
