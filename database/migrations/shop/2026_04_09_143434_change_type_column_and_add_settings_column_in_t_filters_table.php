<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_filters', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('description');
        });

        DB::statement("ALTER TABLE t_filters MODIFY COLUMN type ENUM('select', 'checkbox', 'range', 'entity') NOT NULL DEFAULT 'select'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE t_filters MODIFY COLUMN type ENUM('select', 'checkbox', 'range') NOT NULL DEFAULT 'select'");

        Schema::table('t_filters', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
