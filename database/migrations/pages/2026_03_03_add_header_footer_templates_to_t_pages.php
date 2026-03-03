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
        Schema::table('t_pages', function (Blueprint $table) {
            $table->string('header_template')->nullable()->after('sort');
            $table->string('footer_template')->nullable()->after('header_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_pages', function (Blueprint $table) {
            $table->dropColumn(['header_template', 'footer_template']);
        });
    }
};
