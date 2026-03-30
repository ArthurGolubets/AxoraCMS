<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_products', function (Blueprint $table) {
            $table->json('range_filter_values')->nullable()->after('addition_info');
        });
    }

    public function down(): void
    {
        Schema::table('t_products', function (Blueprint $table) {
            $table->dropColumn('range_filter_values');
        });
    }
};
