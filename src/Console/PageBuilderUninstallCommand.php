<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PageBuilderUninstallCommand extends Command
{
    const MODULE_NAME = 'pagebuilder';

    protected $signature = 'axoracms:pagebuilder-uninstall {--preserve-db : Preserve database tables}';

    protected $description = 'Uninstall AxoraCMS Page Builder Module';

    public function handle(): int
    {
        $this->info('╔═════════════════════════════════════════════╗');
        $this->info('║ AxoraCMS Page Builder Module Uninstaller  ║');
        $this->info('╚═════════════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        // Step 1: Remove copied files
        $this->info('Step 1: Removing copied files...');

        $filesToRemove = [
            app_path('Models/TPage.php'),
            app_path('Http/Controllers/PagesController.php'),
        ];

        foreach ($filesToRemove as $file) {
            if (file_exists($file)) {
                unlink($file);
                $this->info('✓ Removed: '.basename($file));
            }
        }
        $this->newLine();

        // Step 2: Drop tables if not preserving database
        if (! $preserveDb) {
            $this->info('Step 2: Dropping Page Builder tables...');

            $tables = ['t_pages'];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                    $this->info("✓ Dropped table: {$table}");
                }
            }
            $this->newLine();
        } else {
            $this->warn('⚠ Database tables preserved (--preserve-db flag used)');
            $this->newLine();
        }

        // Remove module record
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
        $this->newLine();

        $this->info('╔═════════════════════════════════════════════╗');
        $this->info('║ Page Builder Module uninstalled successfully ║');
        $this->info('╚═════════════════════════════════════════════╝');

        return Command::SUCCESS;
    }
}
