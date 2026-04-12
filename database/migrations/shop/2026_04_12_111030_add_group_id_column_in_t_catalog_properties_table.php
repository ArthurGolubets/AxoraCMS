<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_catalog_properties', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->after('catalog_id')
                ->nullable()->constrained('t_catalog_property_groups')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('t_catalog_properties', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
