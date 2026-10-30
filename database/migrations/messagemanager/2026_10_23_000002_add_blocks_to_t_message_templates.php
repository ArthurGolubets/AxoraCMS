<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email templates are now built from blocks (heading, text, button...). The
 * block structure is kept next to the rendered HTML so a template can be reopened
 * in the visual builder; "body" stays the HTML that is actually sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_message_templates', function (Blueprint $table) {
            $table->json('blocks')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('t_message_templates', function (Blueprint $table) {
            $table->dropColumn('blocks');
        });
    }
};
