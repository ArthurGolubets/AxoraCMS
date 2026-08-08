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
        Schema::create('t_info_block_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('info_block_id')->constrained('t_info_blocks')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('t_info_block_sections')->onDelete('cascade');
            $table->string('name')->comment('Название раздела');
            $table->string('code')->comment('Системное имя (уникальное)');
            $table->string('image')->nullable()->comment('Аватар раздела');
            $table->text('description')->nullable()->comment('Описание раздела');
            $table->integer('sort')->default(500)->comment('Сортировка');
            $table->boolean('is_active')->default(true)->comment('Активность раздела');
            $table->timestamps();

            $table->index(['info_block_id', 'parent_id']);
            $table->index('code');
            $table->index('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_info_block_sections');
    }
};
