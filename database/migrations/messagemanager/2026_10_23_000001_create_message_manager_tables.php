<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module "Менеджер сообщений": email templates, event subscriptions with
 * their ordered actions, and the delivery log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 100)->nullable(); // only a hint: which variables to offer in the editor
            $table->string('subject', 500);
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('t_message_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 100);
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // [{variable, operator, value}], all must match
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['event', 'is_active']);
        });

        Schema::create('t_message_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id');
            $table->string('channel', 20); // telegram | email
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('delay_seconds')->default(0); // pause after the previous action
            $table->json('config')->nullable(); // telegram: chat_ids, text · email: template_id, recipients
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('t_message_subscriptions')->cascadeOnDelete();
            $table->index(['subscription_id', 'sort']);
        });

        Schema::create('t_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('action_id')->nullable();
            $table->string('event', 100);
            $table->string('channel', 20);
            $table->string('recipient', 500)->nullable();
            $table->string('subject', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 20); // sent | failed | skipped
            $table->text('error')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index(['status', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_message_logs');
        Schema::dropIfExists('t_message_actions');
        Schema::dropIfExists('t_message_subscriptions');
        Schema::dropIfExists('t_message_templates');
    }
};
