<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an order item belong to a "set" (набор): a main product plus its
 * companion products, kept together and displayed as one group in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('t_order_items', 'set_group')) {
                $table->string('set_group', 64)->nullable()->after('variant_data');
            }
            if (! Schema::hasColumn('t_order_items', 'set_role')) {
                // single | parent | child
                $table->string('set_role', 16)->default('single')->after('set_group');
            }
        });

        Schema::table('t_order_items', function (Blueprint $table) {
            $table->index('set_group');
        });
    }

    public function down(): void
    {
        Schema::table('t_order_items', function (Blueprint $table) {
            $table->dropIndex(['set_group']);
            $table->dropColumn(['set_group', 'set_role']);
        });
    }
};
