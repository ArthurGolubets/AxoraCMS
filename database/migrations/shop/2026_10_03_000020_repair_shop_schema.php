<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-applies shop schema changes that a reinstall could have skipped.
 *
 * Older versions of `axoracms:shop-uninstall` forgot some migration records,
 * so after "uninstall → install" these migrations were considered done while
 * the recreated tables lacked their columns (e.g. t_catalog_properties.settings).
 * Every step below is idempotent.
 */
return new class extends Migration
{
    /**
     * Earlier shop migrations whose up() is safe to run again.
     *
     * @var array<int, string>
     */
    protected array $idempotent = [
        '2026_09_01_000001_ensure_settings_and_types_on_t_filters_table.php',
        '2026_09_06_120000_add_table_entity_types_and_settings_to_catalog_properties.php',
        '2026_09_06_120001_add_table_entity_types_and_settings_to_characteristic_definitions.php',
        '2026_09_08_000001_scope_t_product_variants_sku_unique.php',
    ];

    public function up(): void
    {
        // The re-applied migrations use MySQL-specific ALTER ... MODIFY statements.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->idempotent as $file) {
            (require __DIR__.'/'.$file)->up();
        }

        if (! Schema::hasTable('t_product_related')) {
            (require __DIR__.'/2026_09_07_000001_create_t_product_related_table.php')->up();
        }
    }

    public function down(): void
    {
        // Nothing to undo: this migration only restores the expected schema.
    }
};
