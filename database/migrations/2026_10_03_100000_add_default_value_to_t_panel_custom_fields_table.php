<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Default value of a custom project field: prefilled into a new field and
 * used on the frontend while the field value is empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_panel_custom_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('t_panel_custom_fields', 'default_value')) {
                $table->json('default_value')->nullable()->after('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_panel_custom_fields', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};
