<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin panel notifications (bell icon in the header). Shared across every
 * administrator; per-admin read state lives in t_admin_notification_reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('t_admin_notifications')) {
            return;
        }

        Schema::create('t_admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g. "order.created"
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable(); // arbitrary payload (order id, amount, ...)
            $table->string('link')->nullable(); // SPA route to open on click
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_admin_notifications');
    }
};
