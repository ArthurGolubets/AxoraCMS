<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_characteristic_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название характеристики
            $table->string('code')->unique(); // Символьный код
            $table->enum('type', ['string', 'number', 'boolean'])->default('string'); // Тип данных
            $table->boolean('multiple')->default(false); // Множественность
            $table->integer('sort_order')->default(500); // Порядок сортировки
            $table->timestamps();

            $table->index('code');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_characteristic_definitions');
    }
};
