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
        Schema::table('t_settings', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('site_description');
            $table->integer('logo_width')->nullable()->after('logo_path');
            $table->integer('logo_height')->nullable()->after('logo_width');
            $table->string('header_template')->default('header1')->after('logo_height');
            $table->string('footer_template')->default('footer1')->after('header_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'logo_width', 'logo_height', 'header_template', 'footer_template']);
        });
    }
};
