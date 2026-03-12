<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InfoBlocksUninstallCommand extends Command
{
    const MODULE_NAME = 'infoblocks';

    protected $signature = 'holartcms:infoblocks-uninstall {--preserve-db : Preserve database tables and data}';
    protected $description = 'Uninstall HolartCMS InfoBlocks Module';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║HolartCMS InfoBlocks Module Uninstaller║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->warn('⚠ WARNING: This will delete all infoblocks data from the database!');
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

        // Step 1: Remove Models
        $this->info('Step 1: Removing infoblocks models...');
        $appModelsPath = app_path('Models');
        $models = ['TInfoBlock.php', 'TInfoBlockField.php', 'TInfoBlockElement.php'];

        foreach ($models as $model) {
            $path = $appModelsPath . '/' . $model;
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$model}");
            }
        }
        $this->newLine();

        // Step 2: Remove Helper
        $this->info('Step 2: Removing TInfoBlock helper...');
        $helperPath = app_path('Helpers/TInfoBlock.php');
        if (File::exists($helperPath)) {
            File::delete($helperPath);
            $this->info("✓ Removed TInfoBlock.php");
        }
        $this->newLine();

        // Step 3: Remove Controllers
        $this->info('Step 3: Removing infoblocks controllers...');
        $appControllersPath = app_path('Http/Controllers');
        $controllers = [
            'InfoBlocksController.php',
            'InfoBlockFieldsController.php',
            'InfoBlockElementsController.php'
        ];

        foreach ($controllers as $controller) {
            $path = $appControllersPath . '/' . $controller;
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$controller}");
            }
        }
        $this->newLine();

        // Step 4: Handle Database
        if (!$preserveDb) {
            $this->info('Step 4: Removing database tables...');

            try {
                Schema::disableForeignKeyConstraints();

                if (Schema::hasTable('t_info_block_elements')) {
                    Schema::dropIfExists('t_info_block_elements');
                    $this->info('✓ Dropped t_info_block_elements table');
                }

                if (Schema::hasTable('t_info_block_fields')) {
                    Schema::dropIfExists('t_info_block_fields');
                    $this->info('✓ Dropped t_info_block_fields table');
                }

                if (Schema::hasTable('t_info_blocks')) {
                    Schema::dropIfExists('t_info_blocks');
                    $this->info('✓ Dropped t_info_blocks table');
                }

                Schema::enableForeignKeyConstraints();
            } catch (\Exception $e) {
                $this->error('❌ Error removing database tables: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 5: Remove Migration Records from Database
            $this->info('Step 5: Removing migration records from database...');
            $migrationFiles = [
                '2024_01_01_000040_create_t_info_blocks_table',
                '2024_01_01_000041_create_t_info_block_fields_table',
                '2024_01_01_000042_create_t_info_block_elements_table',
            ];

            try {
                DB::table('migrations')->whereIn('migration', $migrationFiles)->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 6: Remove Migration Files
            $this->info('Step 6: Removing migration files...');
            foreach ($migrationFiles as $migration) {
                $file = $migration . '.php';
                $path = database_path('migrations/' . $file);
                if (File::exists($path)) {
                    File::delete($path);
                    $this->info("✓ Removed migration {$file}");
                }
            }
            $this->newLine();
        } else {
            $this->info('Step 4: Preserving database tables and data...');
            $this->info('✓ Database preserved');
            $this->newLine();
        }

        // Step 7: Clear Cache
        $this->info('Step 7: Clearing application cache...');
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
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║InfoBlocks Module Uninstalled Successfully!║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->warn('Database tables were preserved. To completely remove infoblocks data,');
            $this->warn('run the command again without the --preserve-db option.');
        }

        return self::SUCCESS;
    }
}
