<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use HolartWeb\HolartCMS\Services\LicenseService;
use Illuminate\Support\Facades\Artisan;

class CallbackInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'callback';

    protected $signature = 'holartcms:callback-user-install';
    protected $description = 'Install HolartCMS Callback Module';

    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║  HolartCMS Callback Module Installer  ║');
        $this->info('╚════════════════════════════════════════╝');
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

        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        try {
            $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/callback';
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

        // Step 3: Clear Cache
        $this->info('Step 3: Clearing application cache...');
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
        $this->info('╔════════════════════════════════════════════╗');
        $this->info('║ Callback Module Installed Successfully!   ║');
        $this->info('╚════════════════════════════════════════════╝');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function checkLicense(): bool
    {
        $savedKey = $this->licenseService->getSavedLicense();

        if ($savedKey && $this->licenseService->checkLicense($savedKey, 'callback-install')) {
            return true;
        }

        $this->warn('No valid license key found.');
        $key = $this->ask('Please enter your license key');

        if (empty($key)) {
            return false;
        }

        if (!$this->licenseService->checkLicense($key, 'callback-install')) {
            $this->error('Invalid license key!');
            return false;
        }

        $this->licenseService->saveLicense($key);
        return true;
    }
}
