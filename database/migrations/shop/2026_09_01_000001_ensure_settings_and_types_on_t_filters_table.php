<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration.
 *
 * On environments where the shop module was reinstalled, the "create t_filters"
 * migration can end up running after the later "add settings column" / "extend
 * type enum" migrations, leaving t_filters without a `settings` column and with
 * a truncated `type` enum. That makes creating category (non-global) filters
 * fail with "Unknown column 'settings'".
 *
 * This migration idempotently brings t_filters up to the expected shape and is a
 * no-op on healthy databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('t_filters')) {
            return;
        }

        if (! Schema::hasColumn('t_filters', 'settings')) {
            Schema::table('t_filters', function (Blueprint $table) {
                $table->json('settings')->nullable()->after('description');
            });
        }

        DB::statement("ALTER TABLE t_filters MODIFY COLUMN type ENUM('select', 'checkbox', 'range', 'entity', 'string') NOT NULL DEFAULT 'select'");
    }

    public function down(): void
    {
        // No-op: the `settings` column and `type` enum values are owned by the
        // original migrations. Reverting here could drop data the app relies on.
    }
};
