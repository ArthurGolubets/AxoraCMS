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
        Schema::create('t_info_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Символьный код инфоблока');
            $table->string('name')->comment('Название инфоблока');
            $table->text('description')->nullable()->comment('Описание инфоблока');
            $table->string('table_name')->unique()->comment('Название таблицы для элементов');
            $table->boolean('is_active')->default(true)->comment('Активность инфоблока');
            $table->boolean('is_favorite')->default(false)->comment('Избранные инфоблоки для sidebar');
            $table->json('settings')->nullable()->comment('Дополнительные настройки');
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_info_blocks');
    }
};
