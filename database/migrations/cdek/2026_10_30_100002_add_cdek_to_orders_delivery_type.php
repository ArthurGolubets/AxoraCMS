<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only MySQL keeps the list of values in the column.
     */
    public function up(): void
    {
        if (Schema::hasTable('t_orders') && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE t_orders MODIFY delivery_type ENUM('pickup', 'courier', 'post', 'cdek') NOT NULL DEFAULT 'pickup'");
        }
    }

    /**
     * The value stays: orders delivered by CDEK must survive removing the module.
     */
    public function down(): void {}
};
