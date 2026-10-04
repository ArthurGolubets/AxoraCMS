<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product SKUs are no longer forced to be unique by the database: the
 * "duplicate SKU" setting may allow two products with the same SKU. The
 * plain index stays for lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('t_products', 't_products_sku_unique')) {
            Schema::table('t_products', function (Blueprint $table) {
                $table->dropUnique('t_products_sku_unique');
            });
        }

        if (! Schema::hasIndex('t_products', 't_products_sku_index')) {
            Schema::table('t_products', function (Blueprint $table) {
                $table->index('sku');
            });
        }
    }

    public function down(): void
    {
        // Restoring uniqueness could fail on duplicates created meanwhile.
    }
};
