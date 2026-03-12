<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Services\LicenseService;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class ShopInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'shop';

    protected $signature = 'holartcms:shop-install';
    protected $description = 'Install HolartCMS Shop Module';

    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   HolartCMS Shop Module Installer   ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // Step 1: Check License
        $this->info('Step 1: Checking license...');
        if (!$this->checkLicense()) {
            $this->error('❌ License verification failed!');
            $this->error('Please contact support to obtain a valid license key.');
            return self::FAILURE;
        }
        $this->info('✓ License verified successfully');
        $this->newLine();

        // Step 2: Run Migrations
        $this->info('Step 2: Running database migrations...');

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        try {
            // Run shop module migrations from package directory
            $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/shop';
            Artisan::call('migrate', [
                '--path' => $migrationsPath,
                '--force' => true
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // Step 3: Build Frontend Assets
        $this->info('Step 3: Building frontend assets...');

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

        // Step 4: Publish Assets
        $this->info('Step 4: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'holart-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 5: Clear Cache
        $this->info('Step 5: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Step 6: Register module in database
        $this->info('Step 6: Registering module...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered successfully (version ' . self::VERSION . ')');
        $this->newLine();

        // Success Message
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   Shop Module Installed Successfully!  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now access the shop features in your admin panel.');
        $this->info('Navigate to: ' . url('/admin/catalog'));
        $this->newLine();

        return self::SUCCESS;
    }

    protected function checkLicense(): bool
    {
        // Check if license key already exists
        $savedKey = $this->licenseService->getSavedLicense();

        if ($savedKey && $this->licenseService->checkLicense($savedKey, 'shop-install')) {
            return true;
        }

        // Request new license key
        $this->warn('No valid license key found.');
        $key = $this->ask('Please enter your license key');

        if (empty($key)) {
            return false;
        }

        // Validate license
        if (!$this->licenseService->checkLicense($key, 'shop-install')) {
            $this->error('Invalid license key!');
            return false;
        }

        // Save license
        $this->licenseService->saveLicense($key);
        return true;
    }
}
