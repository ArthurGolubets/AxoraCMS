<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CommerceUninstallCommand extends Command
{
    const MODULE_NAME = 'commerce';

    protected $signature = 'holartcms:commerce-uninstall {--preserve-db : Preserve database tables}';
    protected $description = 'Uninstall HolartCMS Commerce Module';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║ HolartCMS Commerce Module Uninstaller ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        // Step 1: Remove Models
        $this->info('Step 1: Removing commerce models...');
        $appModelsPath = app_path('Models');
        $models = ['TOrders.php', 'TOrderItems.php', 'TPromocodes.php', 'TPaymentTransaction.php', 'TOrdersData.php'];

        foreach ($models as $model) {
            $modelPath = $appModelsPath . '/' . $model;
            if (file_exists($modelPath)) {
                unlink($modelPath);
                $this->info("✓ Removed {$model}");
            }
        }
        $this->newLine();

        // Step 2: Remove Controllers
        $this->info('Step 2: Removing commerce controllers...');
        $appControllersPath = app_path('Http/Controllers');
        $controllers = [
            'OrdersController.php',
            'PromocodesController.php',
            'PaymentTransactionsController.php',
            'OrdersDataController.php'
        ];

        foreach ($controllers as $controller) {
            $controllerPath = $appControllersPath . '/' . $controller;
            if (file_exists($controllerPath)) {
                unlink($controllerPath);
                $this->info("✓ Removed {$controller}");
            }
        }
        $this->newLine();

        // Step 3: Drop Database Tables (if not preserving)
        if (!$preserveDb) {
            $this->info('Step 3: Dropping database tables...');
            $tables = [
                't_orders_data',
                't_payment_transactions',
                't_promocodes',
                't_order_items',
                't_orders'
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                    $this->info("✓ Dropped table {$table}");
                }
            }

            // Remove migration records from database
            $this->info('Removing migration records from database...');
            $migrationFiles = [
                '2024_01_01_000030_create_t_orders_table.php',
                '2024_01_01_000031_create_t_order_items_table.php',
                '2024_01_01_000032_create_t_promocodes_table.php',
                '2024_01_01_000033_create_t_payment_transactions_table.php',
                '2024_01_01_000034_create_t_orders_data_table.php',
            ];

            try {
                DB::table('migrations')->whereIn('migration', array_map(function($file) {
                    return str_replace('.php', '', $file);
                }, $migrationFiles))->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }

            // Remove migration files
            $this->info('Removing migration files...');
            foreach ($migrationFiles as $file) {
                $migrationPath = database_path('migrations/' . $file);
                if (file_exists($migrationPath)) {
                    unlink($migrationPath);
                    $this->info("✓ Removed migration {$file}");
                }
            }
        } else {
            $this->warn('Step 3: Skipped database table removal (--preserve-db flag)');
        }
        $this->newLine();

        // Step 4: Clear Cache
        $this->info('Step 4: Clearing application cache...');
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
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║ Commerce Module Uninstalled Successfully ║');
        $this->info('╚═══════════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->info('Note: Database tables were preserved.');
            $this->info('To remove them, run: php artisan holartcms:commerce-uninstall');
        }

        return self::SUCCESS;
    }
}
