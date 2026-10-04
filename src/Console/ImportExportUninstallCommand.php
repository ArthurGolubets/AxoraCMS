<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Removes the "Импорт/Экспорт" module: task history and generated files.
 */
class ImportExportUninstallCommand extends Command
{
    const MODULE_NAME = 'importexport';

    protected $signature = 'axoracms:importexport-uninstall {--preserve-db : Preserve database tables and data}';

    protected $description = 'Uninstall AxoraCMS Import/Export Module';

    public function handle(): int
    {
        if (! $this->option('preserve-db')) {
            Schema::dropIfExists('t_import_export_tasks');
            Storage::disk('local')->deleteDirectory('import-export');

            $migrations = array_map(
                fn ($file) => basename($file, '.php'),
                glob(dirname(__DIR__, 2).'/database/migrations/importexport/*.php') ?: []
            );
            DB::table('migrations')->whereIn('migration', $migrations)->delete();
            $this->info('✓ Tasks and files removed');
        }

        Artisan::call('route:clear');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        return self::SUCCESS;
    }
}
