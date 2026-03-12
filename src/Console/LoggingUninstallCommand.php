<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoggingUninstallCommand extends Command
{
    const MODULE_NAME = 'logging';

    protected $signature = 'holartcms:logging-uninstall {--preserve-db : Preserve database tables and data}';
    protected $description = 'Uninstall HolartCMS Logging Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ HolartCMS Logging Module Uninstaller║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->warn('⚠ WARNING: This will delete all logging data from the database!');
            // Only ask for confirmation if running in interactive console
            if ($this->input->isInteractive() && defined('STDIN')) {
                if (!$this->confirm('Are you sure you want to continue?', false)) {
                    $this->info('Uninstallation cancelled.');
                    return self::SUCCESS;
                }
            } else {
                // When called from web interface, proceed without confirmation
                $this->info('Running in non-interactive mode, proceeding with uninstallation...');
            }
        }

        // Step 1: Remove Controller
        $this->info('Step 1: Removing logs controller...');
        $appControllersPath = app_path('Http/Controllers');
        $controller = 'LogsController.php';

        $path = $appControllersPath . '/' . $controller;
        if (File::exists($path)) {
            File::delete($path);
            $this->info("✓ Removed {$controller}");
        }
        $this->newLine();

        // Step 2: Handle Database
        if (!$preserveDb) {
            $this->info('Step 2: Removing database tables...');

            try {
                Schema::disableForeignKeyConstraints();

                if (Schema::hasTable('t_admin_actions')) {
                    Schema::dropIfExists('t_admin_actions');
                    $this->info('✓ Dropped t_admin_actions table');
                }

                Schema::enableForeignKeyConstraints();
            } catch (\Exception $e) {
                $this->error('❌ Error removing database tables: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 3: Remove Migration Records from Database
            $this->info('Step 3: Removing migration records from database...');
            $migrationFile = '2026_02_27_125658_create_t_admin_actions_table';

            try {
                DB::table('migrations')->where('migration', $migrationFile)->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 4: Remove Migration File
            $this->info('Step 4: Removing migration file...');
            $path = database_path('migrations/' . $migrationFile . '.php');
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed migration {$migrationFile}.php");
            }
            $this->newLine();
        } else {
            $this->info('Step 2: Preserving database tables and data...');
            $this->info('✓ Database preserved');
            $this->newLine();
        }

        // Step 5: Clear Cache
        $this->info('Step 5: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Remove module record
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
        $this->newLine();

        // Success Message
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║Logging Module Uninstalled Successfully!║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->warn('Database tables were preserved. To completely remove logging data,');
            $this->warn('run the command again without the --preserve-db option.');
        }

        return self::SUCCESS;
    }
}
