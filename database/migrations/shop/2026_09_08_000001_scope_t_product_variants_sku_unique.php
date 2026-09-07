<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A variant SKU only has to be unique within its own product.
 *
 * The same product may legitimately be added as a variant of several other
 * products (e.g. "Гвозди" as a variant of both "Шурупы" and "Болты"), which the
 * old global unique(sku) forbade.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Resolve any pre-existing (product_id, sku) collisions before adding the
        // composite unique — append _2, _3, ... to the later rows.
        $dupes = DB::table('t_product_variants')
            ->select('product_id', 'sku', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('product_id', 'sku')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupes as $dupe) {
            $rows = DB::table('t_product_variants')
                ->where('product_id', $dupe->product_id)
                ->where('sku', $dupe->sku)
                ->orderBy('id')
                ->pluck('id');

            $n = 1;
            foreach ($rows as $rowId) {
                if ($rowId === $dupe->keep_id) {
                    continue;
                }
                $n++;
                DB::table('t_product_variants')->where('id', $rowId)->update([
                    'sku' => $dupe->sku.'_'.$n,
                ]);
            }
        }

        if ($this->indexExists('t_product_variants_sku_unique')) {
            Schema::table('t_product_variants', function (Blueprint $table) {
                $table->dropUnique('t_product_variants_sku_unique');
            });
        }

        if (! $this->indexExists('t_product_variants_product_id_sku_unique')) {
            Schema::table('t_product_variants', function (Blueprint $table) {
                $table->unique(['product_id', 'sku']);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('t_product_variants_product_id_sku_unique')) {
            Schema::table('t_product_variants', function (Blueprint $table) {
                $table->dropUnique('t_product_variants_product_id_sku_unique');
            });
        }

        if (! $this->indexExists('t_product_variants_sku_unique')) {
            Schema::table('t_product_variants', function (Blueprint $table) {
                $table->unique('sku');
            });
        }
    }

    private function indexExists(string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 't_product_variants')
            ->where('index_name', $name)
            ->exists();
    }
};
