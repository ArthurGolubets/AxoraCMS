<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\Commerce\TOrdersData;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CommerceInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'commerce';

    protected $signature = 'axoracms:commerce-install';

    protected $description = 'Install AxoraCMS Commerce Module';

    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║  AxoraCMS Commerce Module Installer  ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        // Step 1: Check Shop Module Dependency
        $this->info('Step 1: Checking Shop module dependency...');
        if (! $this->checkShopModuleInstalled()) {
            $this->error('❌ Shop module is not installed!');
            $this->error('Commerce module requires Shop module to be installed first.');
            $this->error('Please install Shop module using: php artisan axoracms:shop-install');

            return self::FAILURE;
        }
        $this->info('✓ Shop module is installed');
        $this->newLine();

        // Step 2: Check License
        $this->info('Step 2: Checking license...');
        if (! $this->checkLicense()) {
            $this->error('❌ License verification failed!');
            $this->error('Please contact support to obtain a valid license key.');

            return self::FAILURE;
        }
        $this->info('✓ License verified successfully');
        $this->newLine();

        // Step 3: Run Migrations
        $this->info('Step 3: Running database migrations...');

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (! file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        try {
            // Run commerce module migrations directly from the package directory.
            // Do NOT copy migration files into the application's database/migrations folder.
            // Absolute path + --realpath so it also works on Windows.
            $migrationsPath = $packagePath.'/database/migrations/commerce';
            Artisan::call('migrate', [
                '--path' => $migrationsPath,
                '--realpath' => true,
                '--force' => true,
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->newLine();

        // Step 3b: Seed default order settings
        $this->info('Seeding default order settings...');
        $this->seedOrderSettings();
        $this->newLine();

        // Step 4: Build Frontend Assets
        $this->info('Step 4: Building frontend assets...');

        if (file_exists($packagePath.'/package.json')) {
            $this->info('Installing npm dependencies...');
            exec("cd {$packagePath} && npm install 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $this->warn('⚠ npm install encountered issues');
                $this->warn('⚠ Skipping asset build due to npm install issues');
            } else {
                $this->info('✓ npm dependencies installed');

                $this->info('Building assets...');
                exec("cd {$packagePath} && npm run build 2>&1", $output, $returnVar);

                if ($returnVar !== 0) {
                    $this->warn('⚠ Asset build failed - you may need to build assets manually');
                } else {
                    $this->info('✓ Assets built successfully');
                }
            }
        } else {
            $this->warn('⚠ package.json not found, skipping asset build');
        }
        $this->newLine();

        // Step 5: Publish Assets
        $this->info('Step 5: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'axora-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 6: Clear Cache
        $this->info('Step 6: Clearing application cache...');
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
        $this->info('Navigate to: '.url('/admin/orders'));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Create the default order-settings rows in t_orders_data.
     *
     * Idempotent: existing keys keep their current value, only missing keys
     * are inserted. Previously these rows only appeared after an admin opened
     * "Настройки заказов" and pressed Save.
     */
    protected function seedOrderSettings(): void
    {
        if (! Schema::hasTable('t_orders_data')) {
            $this->warn('⚠ t_orders_data not found, skipping order settings seed');

            return;
        }

        $defaults = [
            // General
            ['order_notifications_enabled', true, TOrdersData::TYPE_BOOLEAN],
            ['min_order_amount', '0', TOrdersData::TYPE_STRING],
            // Delivery
            ['delivery_pickup_enabled', true, TOrdersData::TYPE_BOOLEAN],
            ['delivery_courier_enabled', true, TOrdersData::TYPE_BOOLEAN],
            ['delivery_courier_price', '0', TOrdersData::TYPE_STRING],
            ['delivery_post_enabled', false, TOrdersData::TYPE_BOOLEAN],
            ['delivery_post_price', '0', TOrdersData::TYPE_STRING],
            ['delivery_zones_enabled', false, TOrdersData::TYPE_BOOLEAN],
            ['free_delivery_from', '0', TOrdersData::TYPE_STRING],
            ['delivery_zones', [], TOrdersData::TYPE_JSON],
            // Payment
            ['payment_online_enabled', true, TOrdersData::TYPE_BOOLEAN],
            ['payment_provider', 'transfer', TOrdersData::TYPE_STRING],
            ['payment_cash_enabled', true, TOrdersData::TYPE_BOOLEAN],
            // Checkout form
            ['require_phone', true, TOrdersData::TYPE_BOOLEAN],
            ['require_email', true, TOrdersData::TYPE_BOOLEAN],
            ['show_comments_field', true, TOrdersData::TYPE_BOOLEAN],
        ];

        $created = 0;
        foreach ($defaults as [$key, $value, $type]) {
            if (TOrdersData::where('key', $key)->exists()) {
                continue;
            }

            TOrdersData::setValue($key, $value, $type);
            $created++;
        }

        $this->info($created > 0
            ? "✓ Order settings seeded ({$created} keys)"
            : '✓ Order settings already present');
    }

    protected function checkShopModuleInstalled(): bool
    {
        // Check if shop module is registered in t_modules table
        if (Schema::hasTable('t_modules')) {
            $shopModule = TModule::where('module_name', 'shop')->first();
            if ($shopModule) {
                return true;
            }
        }

        // Fallback to table check
        return Schema::hasTable('t_catalogs') && Schema::hasTable('t_products');
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
            if (! $this->licenseService->checkLicense($key, 'commerce-install')) {
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
