<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopUninstallCommand extends Command
{
    const MODULE_NAME = 'shop';

    protected $signature = 'axoracms:shop-uninstall {--preserve-db : Preserve database tables and data}';
    protected $description = 'Uninstall AxoraCMS Shop Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║  AxoraCMS Shop Module Uninstaller  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->warn('⚠ WARNING: This will delete all shop data from the database!');
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
        $this->info('Step 1: Removing shop models...');
        $appModelsPath = app_path('Models');
        $models = ['TCatalog.php', 'TProduct.php', 'TProductVariant.php', 'TFilter.php', 'TFilterValue.php'];

        foreach ($models as $model) {
            $path = $appModelsPath . '/' . $model;
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$model}");
            }
        }
        $this->newLine();

        // Step 2: Remove Controllers
        $this->info('Step 2: Removing shop controllers...');
        $appControllersPath = app_path('Http/Controllers');
        $controllers = ['CatalogController.php', 'ProductController.php', 'FilterController.php'];

        foreach ($controllers as $controller) {
            $path = $appControllersPath . '/' . $controller;
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$controller}");
            }
        }
        $this->newLine();

        // Step 3: Handle Database
        if (!$preserveDb) {
            $this->info('Step 3: Removing database tables...');

            try {
                Schema::disableForeignKeyConstraints();

                // Drop property-related tables first (they have foreign keys)
                if (Schema::hasTable('t_product_property_values')) {
                    Schema::dropIfExists('t_product_property_values');
                    $this->info('✓ Dropped t_product_property_values table');
                }

                if (Schema::hasTable('t_catalog_properties')) {
                    Schema::dropIfExists('t_catalog_properties');
                    $this->info('✓ Dropped t_catalog_properties table');
                }

                // Drop filter-related tables (they have foreign keys)
                if (Schema::hasTable('t_product_filter_values')) {
                    Schema::dropIfExists('t_product_filter_values');
                    $this->info('✓ Dropped t_product_filter_values table');
                }

                if (Schema::hasTable('t_filter_values')) {
                    Schema::dropIfExists('t_filter_values');
                    $this->info('✓ Dropped t_filter_values table');
                }

                if (Schema::hasTable('t_filters')) {
                    Schema::dropIfExists('t_filters');
                    $this->info('✓ Dropped t_filters table');
                }

                // Then drop product and catalog tables
                if (Schema::hasTable('t_product_variants')) {
                    Schema::dropIfExists('t_product_variants');
                    $this->info('✓ Dropped t_product_variants table');
                }

                if (Schema::hasTable('t_products')) {
                    Schema::dropIfExists('t_products');
                    $this->info('✓ Dropped t_products table');
                }

                if (Schema::hasTable('t_catalogs')) {
                    Schema::dropIfExists('t_catalogs');
                    $this->info('✓ Dropped t_catalogs table');
                }

                Schema::enableForeignKeyConstraints();
            } catch (\Exception $e) {
                $this->error('❌ Error removing database tables: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 4: Remove Migration Records from Database
            $this->info('Step 4: Removing migration records from database...');
            $migrationFiles = [
                '2024_01_01_000010_create_t_catalogs_table.php',
                '2024_01_01_000011_create_t_products_table.php',
                '2024_01_01_000012_create_t_product_variants_table.php',
                '2024_01_01_000013_add_main_image_to_products.php',
                '2024_01_01_000018_add_range_filter_values_to_t_products_table.php',
                '2026_03_03_000070_create_t_filters_table.php',
                '2026_03_03_000071_create_t_filter_values_table.php',
                '2026_03_03_000072_create_t_product_filter_values_table.php',
                '2026_03_29_000090_create_t_catalog_properties_table.php',
                '2026_03_29_000091_create_t_product_property_values_table.php',
            ];

            try {
                DB::table('migrations')->whereIn('migration', array_map(function($file) {
                    return str_replace('.php', '', $file);
                }, $migrationFiles))->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 5: Remove Migration Files
            $this->info('Step 5: Removing migration files...');
            foreach ($migrationFiles as $file) {
                $path = database_path('migrations/' . $file);
                if (File::exists($path)) {
                    File::delete($path);
                    $this->info("✓ Removed migration {$file}");
                }
            }
            $this->newLine();
        } else {
            $this->info('Step 3: Preserving database tables and data...');
            $this->info('✓ Database preserved');
            $this->newLine();
        }

        // Step 6: Clear Cache
        $this->info('Step 6: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Success Message
        // Remove module record from database
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
        $this->newLine();

        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ Shop Module Uninstalled Successfully! ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->warn('Database tables were preserved. To completely remove shop data,');
            $this->warn('run the command again without the --preserve-db option.');
        }

        return self::SUCCESS;
    }
}
