<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagesUninstallCommand extends Command
{
    protected $signature = 'holartcms:pages-uninstall {--preserve-db : Preserve database tables and data} {--force : Force uninstall without confirmation}';
    protected $description = 'Uninstall HolartCMS Pages Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ HolartCMS Pages Module Uninstaller  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');
        $force = $this->option('force');

        if (!$preserveDb && !$force && !$this->confirm('This will remove all pages data. Are you sure?')) {
            $this->info('Uninstall cancelled.');
            return self::SUCCESS;
        }

        if ($preserveDb) {
            $this->warn('Database preservation mode: Tables and data will NOT be deleted.');
            $this->newLine();
        }

        // Step 1: Drop tables (skip if preserve-db)
        if (!$preserveDb) {
            $this->info('Step 1: Dropping database tables...');
            try {
                // Get database connection
                $connection = Schema::getConnection();
                $dbName = $connection->getDatabaseName();

                // Disable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Drop foreign keys first from t_page_blocks
                if (Schema::hasTable('t_page_blocks')) {
                    $this->info('Dropping foreign keys from t_page_blocks...');

                    // Get all foreign keys for t_page_blocks
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = 't_page_blocks'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$dbName]);

                    foreach ($foreignKeys as $fk) {
                        try {
                            DB::statement("ALTER TABLE t_page_blocks DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                            $this->info("✓ Dropped foreign key: {$fk->CONSTRAINT_NAME}");
                        } catch (\Exception $e) {
                            $this->warn("⚠ Could not drop foreign key {$fk->CONSTRAINT_NAME}: " . $e->getMessage());
                        }
                    }
                }

                // Drop foreign keys from t_menu_items
                if (Schema::hasTable('t_menu_items')) {
                    $this->info('Dropping foreign keys from t_menu_items...');

                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = 't_menu_items'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$dbName]);

                    foreach ($foreignKeys as $fk) {
                        try {
                            DB::statement("ALTER TABLE t_menu_items DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                            $this->info("✓ Dropped foreign key: {$fk->CONSTRAINT_NAME}");
                        } catch (\Exception $e) {
                            $this->warn("⚠ Could not drop foreign key {$fk->CONSTRAINT_NAME}: " . $e->getMessage());
                        }
                    }
                }

                // Now drop pages tables
                $this->info('Dropping pages tables...');
                Schema::dropIfExists('t_page_blocks');
                $this->info('✓ Dropped t_page_blocks');

                Schema::dropIfExists('t_page_block_types');
                $this->info('✓ Dropped t_page_block_types');

                Schema::dropIfExists('t_pages');
                $this->info('✓ Dropped t_pages');

                // Drop menus tables
                $this->info('Dropping menus tables...');
                Schema::dropIfExists('t_menu_items');
                $this->info('✓ Dropped t_menu_items');

                Schema::dropIfExists('t_menus');
                $this->info('✓ Dropped t_menus');

                // Re-enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $this->info('✓ All tables dropped successfully');
            } catch (\Exception $e) {
                $this->error('❌ Failed to drop tables: ' . $e->getMessage());
                $this->error('Stack trace: ' . $e->getTraceAsString());
                // Re-enable foreign key checks in case of error
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } catch (\Exception $ex) {
                    // Ignore
                }
                return self::FAILURE;
            }
            $this->newLine();
        } else {
            $this->info('Step 1: Skipping database tables (preserved)');
            $this->newLine();
        }

        // Step 2: Remove migrations
        $this->info('Step 2: Removing migrations...');
        $migrationFiles = [
            '2026_03_02_000050_create_t_pages_table.php',
            '2026_03_02_000051_create_t_page_block_types_table.php',
            '2026_03_02_000052_create_t_page_blocks_table.php',
            '2026_03_02_000053_update_pages_add_container_support.php',
            '2026_03_03_000060_create_t_menus_table.php',
            '2026_03_03_000061_create_t_menu_items_table.php',
        ];

        foreach ($migrationFiles as $file) {
            $migrationPath = database_path('migrations/' . $file);
            if (file_exists($migrationPath)) {
                unlink($migrationPath);
                $this->info("✓ Removed migration {$file}");
            }
        }

        // Remove from migrations table
        try {
            foreach ($migrationFiles as $file) {
                DB::table('migrations')->where('migration', pathinfo($file, PATHINFO_FILENAME))->delete();
            }
            $this->info('✓ Migration records removed');
        } catch (\Exception $e) {
            $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
        }
        $this->newLine();

        // Step 3: Remove models
        $this->info('Step 3: Removing models...');
        $models = ['TPage.php', 'TPageBlock.php', 'TPageBlockType.php', 'TMenu.php', 'TMenuItem.php'];
        foreach ($models as $model) {
            $modelPath = app_path('Models/' . $model);
            if (file_exists($modelPath)) {
                unlink($modelPath);
                $this->info("✓ Removed {$model}");
            }
        }
        $this->newLine();

        // Step 4: Remove controllers
        $this->info('Step 4: Removing controllers...');
        $controllers = [
            'PagesController.php',
            'PageBlocksController.php',
            'PageBlockTypesController.php',
            'MenusController.php',
            'MenuItemsController.php'
        ];

        foreach ($controllers as $controller) {
            $controllerPath = app_path('Http/Controllers/' . $controller);
            if (file_exists($controllerPath)) {
                unlink($controllerPath);
                $this->info("✓ Removed {$controller}");
            }
        }
        $this->newLine();

        // Step 5: Remove Blade templates
        $this->info('Step 5: Removing blade templates...');
        $bladeFiles = [
            'blocks/hero-1.blade.php',
            'blocks/hero-2.blade.php',
            'blocks/text.blade.php',
            'blocks/rich-text.blade.php',
            'blocks/cards.blade.php',
            'blocks/products.blade.php',
            'blocks/catalogs.blade.php',
            'blocks/features.blade.php',
            'blocks/breadcrumbs.blade.php',
            'blocks/slider.blade.php',
            'blocks/container-50-50.blade.php',
            'blocks/container-33-33-33.blade.php',
            'blocks/container-25-75.blade.php',
            'page-renderer.blade.php',
        ];

        $viewsPath = resource_path('views/components');
        foreach ($bladeFiles as $file) {
            $filePath = $viewsPath . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
                $this->info("✓ Removed {$file}");
            }
        }

        // Try to remove empty directories
        $blocksDir = $viewsPath . '/blocks';
        if (is_dir($blocksDir) && count(scandir($blocksDir)) == 2) { // only . and ..
            rmdir($blocksDir);
            $this->info('✓ Removed blocks directory');
        }
        $this->newLine();

        // Step 6: Clear Cache
        $this->info('Step 6: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Success Message
        $this->info('╔═══════════════════════════════════════╗');
        $this->info('║Pages Module Uninstalled Successfully!║');
        $this->info('╚═══════════════════════════════════════╝');
        $this->newLine();

        return self::SUCCESS;
    }
}
