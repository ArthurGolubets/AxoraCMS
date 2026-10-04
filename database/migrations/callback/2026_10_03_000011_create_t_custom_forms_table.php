<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-defined forms ("Своя форма"): questionnaires, Q&A, applications, ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_custom_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('admin_can_create')->default(false)->comment('Администратор может создавать записи');
            $table->boolean('notify_admin')->default(true)->comment('Уведомлять администратора о новых записях');
            $table->string('success_message')->nullable();
            $table->unsignedInteger('sort')->default(500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_custom_forms');
    }
};
