<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Info block field flags:
 *  - is_system: shown in the element form but read-only for the admin;
 *  - is_hidden: not shown in the element form at all;
 *  - default_value: initial value for new elements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_info_block_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('t_info_block_fields', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_multiple')->comment('Системное поле (только просмотр)');
            }
            if (! Schema::hasColumn('t_info_block_fields', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false)->after('is_system')->comment('Скрытое поле');
            }
            if (! Schema::hasColumn('t_info_block_fields', 'default_value')) {
                $table->json('default_value')->nullable()->after('is_hidden')->comment('Значение по умолчанию');
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_info_block_fields', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'is_hidden', 'default_value']);
        });
    }
};
