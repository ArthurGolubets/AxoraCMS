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
        Schema::table('t_products', function (Blueprint $table) {
            $table->string('1c_id')->nullable()->unique()->after('sku');
            $table->integer('quantity')->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_products', function (Blueprint $table) {
            $table->dropColumn(['1c_id', 'quantity']);
        });
    }
};
