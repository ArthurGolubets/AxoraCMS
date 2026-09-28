<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-administrator read state for t_admin_notifications. A row's presence
 * means that administrator has read that notification.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_admin_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('administrator_id');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['notification_id', 'administrator_id'], 't_admin_notification_reads_unique');
            $table->index('administrator_id');

            $table->foreign('notification_id')->references('id')->on('t_admin_notifications')->cascadeOnDelete();
            $table->foreign('administrator_id')->references('id')->on('t_administrators')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_admin_notification_reads');
    }
};
