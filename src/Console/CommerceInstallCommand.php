<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use HolartWeb\HolartCMS\Services\LicenseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CommerceInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'commerce';

    protected $signature = 'holartcms:commerce-install';
    protected $description = 'Install HolartCMS Commerce Module';

    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║  HolartCMS Commerce Module Installer  ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        // Step 1: Check Shop Module Dependency
        $this->info('Step 1: Checking Shop module dependency...');
        if (!$this->checkShopModuleInstalled()) {
            $this->error('❌ Shop module is not installed!');
            $this->error('Commerce module requires Shop module to be installed first.');
            $this->error('Please install Shop module using: php artisan holartcms:shop-install');
            return self::FAILURE;
        }
        $this->info('✓ Shop module is installed');
        $this->newLine();

        // Step 2: Check License
        $this->info('Step 2: Checking license...');
        if (!$this->checkLicense()) {
            $this->error('❌ License verification failed!');
            $this->error('Please contact support to obtain a valid license key.');
            return self::FAILURE;
        }
        $this->info('✓ License verified successfully');
        $this->newLine();

        // Step 3: Copy Commerce Models
        $this->info('Step 3: Copying commerce models...');

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        $appModelsPath = app_path('Models');

        $models = ['TOrders.php', 'TOrderItems.php', 'TPromocodes.php', 'TPaymentTransaction.php', 'TOrdersData.php'];
        foreach ($models as $model) {
            $source = $packagePath . '/src/Models/Commerce/' . $model;
            $destination = $appModelsPath . '/' . $model;

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied {$model}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }
        $this->newLine();

        // Step 4: Copy Commerce Controllers
        $this->info('Step 4: Copying commerce controllers...');
        $appControllersPath = app_path('Http/Controllers');

        $controllers = [
            'OrdersController.php',
            'PromocodesController.php',
            'PaymentTransactionsController.php',
            'OrdersDataController.php'
        ];

        foreach ($controllers as $controller) {
            $source = $packagePath . '/src/Http/Controllers/Commerce/' . $controller;
            $destination = $appControllersPath . '/' . $controller;

            if (file_exists($source)) {
                $content = file_get_contents($source);
                // Update namespace from package to app
                $content = str_replace(
                    'namespace HolartWeb\HolartCMS\Http\Controllers\Commerce;',
                    'namespace App\Http\Controllers;',
                    $content
                );
                $content = str_replace(
                    'use Illuminate\Routing\Controller;',
                    '',
                    $content
                );
                file_put_contents($destination, $content);
                $this->info("✓ Copied {$controller}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }
        $this->newLine();

        // Step 5: Copy and Run Migrations
        $this->info('Step 5: Copying and running database migrations...');
        $migrationFiles = [
            '2024_01_01_000030_create_t_orders_table.php',
            '2024_01_01_000031_create_t_order_items_table.php',
            '2024_01_01_000032_create_t_promocodes_table.php',
            '2024_01_01_000033_create_t_payment_transactions_table.php',
            '2024_01_01_000034_create_t_orders_data_table.php',
        ];

        foreach ($migrationFiles as $file) {
            $source = $packagePath . '/database/migrations/commerce/' . $file;
            $destination = database_path('migrations/' . $file);

            // Remove old migration file if exists
            if (file_exists($destination)) {
                unlink($destination);
                $this->info("✓ Removed old migration {$file}");
            }

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied migration {$file}");
            } else {
                $this->warn("⚠ Source migration not found: {$source}");
            }
        }

        // Check if tables already exist
        $tables = ['t_orders', 't_order_items', 't_promocodes', 't_payment_transactions', 't_orders_data'];
        $existingTables = [];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $existingTables[] = $table;
            }
        }

        if (!empty($existingTables)) {
            $this->info('✓ Tables already exist: ' . implode(', ', $existingTables));
            $this->info('✓ Skipping migrations (database already configured)');
        } else {
            // Remove migration records from database
            try {
                \DB::table('migrations')->whereIn('migration', array_map(function($file) {
                    return str_replace('.php', '', $file);
                }, $migrationFiles))->delete();
                $this->info('✓ Cleaned migration records');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not clean migration records: ' . $e->getMessage());
            }

            try {
                // Run only commerce module migrations
                foreach ($migrationFiles as $file) {
                    $migrationPath = database_path('migrations/' . $file);
                    if (file_exists($migrationPath)) {
                        Artisan::call('migrate', [
                            '--path' => 'database/migrations/' . $file,
                            '--force' => true
                        ]);
                    }
                }
                $this->info('✓ Migrations completed successfully');
            } catch (\Exception $e) {
                $this->error('❌ Migration failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }
        $this->newLine();

        // Step 6: Build Frontend Assets
        $this->info('Step 6: Building frontend assets...');

        if (file_exists($packagePath . '/package.json')) {
            $this->info('Installing npm dependencies...');
            exec("cd {$packagePath} && npm install 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $this->warn('⚠ npm install encountered issues');
            } else {
                $this->info('✓ npm dependencies installed');
            }

            $this->info('Building assets...');
            exec("cd {$packagePath} && npm run build 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $this->error('❌ Asset build failed');
                return self::FAILURE;
            }
            $this->info('✓ Assets built successfully');
        } else {
            $this->warn('⚠ package.json not found, skipping asset build');
        }
        $this->newLine();

        // Step 7: Publish Assets
        $this->info('Step 7: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'holart-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 8: Clear Cache
        $this->info('Step 8: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Register module
        $this->info('Registering module installation...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');
        $this->newLine();

        // Success Message
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║ Commerce Module Installed Successfully!  ║');
        $this->info('╚═══════════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now access the commerce features in your admin panel.');
        $this->info('Navigate to: ' . url('/admin/orders'));
        $this->newLine();

        return self::SUCCESS;
    }

    protected function checkShopModuleInstalled(): bool
    {
        return Schema::hasTable('t_catalogs') &&
               Schema::hasTable('t_products');
    }

    protected function checkLicense(): bool
    {
        // Check if license key already exists
        $savedKey = $this->licenseService->getSavedLicense();

        if ($savedKey && $this->licenseService->checkLicense($savedKey, 'commerce-install')) {
            return true;
        }

        // If running in console (not web), request new license key
        if ($this->input->isInteractive()) {
            $this->warn('No valid license key found.');
            $key = $this->ask('Please enter your license key');

            if (empty($key)) {
                return false;
            }

            // Validate license
            if (!$this->licenseService->checkLicense($key, 'commerce-install')) {
                $this->error('Invalid license key!');
                return false;
            }

            // Save license
            $this->licenseService->saveLicense($key);
            return true;
        }

        // If not interactive (web call), just use saved license or skip
        if ($savedKey) {
            return true;
        }

        $this->warn('⚠ No license key found, but continuing installation...');
        return true;
    }
}
