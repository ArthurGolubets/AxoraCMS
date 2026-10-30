<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_cdek_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('state', 16)->index();
            $table->uuid('uuid')->nullable();
            $table->string('track_number', 64)->nullable();
            $table->string('cdek_status', 64)->nullable();
            $table->string('cdek_status_name')->nullable();
            $table->timestamp('cdek_status_at')->nullable();
            $table->string('delivery_point', 32)->nullable();
            $table->unsignedInteger('city_code')->nullable();
            $table->string('address', 500)->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedInteger('length')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cdek_shipments');
    }
};
