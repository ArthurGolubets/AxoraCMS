<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Background import / export tasks (module "Импорт/Экспорт"). Progress is
 * stored here so a running task survives page reloads and is visible to
 * every administrator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_import_export_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // import | export
            $table->string('entity', 50); // products | orders | infoblock | comments | ...
            $table->string('status', 20)->default('draft'); // draft | pending | running | completed | failed | cancelled
            $table->string('title');
            $table->unsignedBigInteger('administrator_id')->nullable();
            $table->string('format', 10)->nullable(); // xlsx | csv | xml
            $table->string('source_path')->nullable(); // uploaded file (import)
            $table->string('original_name')->nullable();
            $table->string('result_path')->nullable(); // generated file (export)
            $table->json('options')->nullable(); // mapping, filters, ...
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->json('stats')->nullable(); // created / updated / skipped / failed
            $table->json('errors')->nullable(); // first error messages
            $table->text('message')->nullable(); // fatal error
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_import_export_tasks');
    }
};
