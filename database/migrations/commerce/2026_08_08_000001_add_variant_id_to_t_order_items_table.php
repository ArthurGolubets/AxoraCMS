<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            $table->json('variant_data')->nullable()->after('variant_id');

            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('t_order_items', function (Blueprint $table) {
            $table->dropIndex(['variant_id']);
            $table->dropColumn(['variant_id', 'variant_data']);
        });
    }
};
