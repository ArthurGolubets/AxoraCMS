<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an administrator first opened the request — drives the "new requests"
 * sidebar badge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_user_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('t_user_requests', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('user_id');
                $table->index('viewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_user_requests', function (Blueprint $table) {
            $table->dropIndex(['viewed_at']);
            $table->dropColumn('viewed_at');
        });
    }
};
