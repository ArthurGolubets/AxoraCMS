<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class PageBuilderUninstallCommand extends Command
{
    const MODULE_NAME = 'pagebuilder';

    protected $signature = 'holartcms:pagebuilder-uninstall {--preserve-db : Preserve database tables}';
    protected $description = 'Uninstall HolartCMS Page Builder Module';

    public function handle(): int
    {
        $this->info('╔═════════════════════════════════════════════╗');
        $this->info('║ HolartCMS Page Builder Module Uninstaller  ║');
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
                $this->info("✓ Removed: " . basename($file));
            }
        }
        $this->newLine();

        // Step 2: Drop tables if not preserving database
        if (!$preserveDb) {
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
