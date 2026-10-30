<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_bitrix24_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 64)->index();
            $table->string('form_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('entity', 16)->default('lead');
            $table->string('contact_mode', 16)->default('none');
            $table->string('company_mode', 16)->default('none');
            $table->boolean('send_products')->default(false);
            $table->json('mapping')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('t_bitrix24_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('t_bitrix24_rules')->nullOnDelete();
            $table->string('rule_name')->nullable();
            $table->string('event', 64);
            $table->string('status', 16)->index();
            $table->string('entity', 16)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_bitrix24_logs');
        Schema::dropIfExists('t_bitrix24_rules');
    }
};
