<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class LoggingInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'logging';

    protected $signature = 'axoracms:logging-install';
    protected $description = 'Install AxoraCMS Logging Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║  AxoraCMS Logging Module Installer ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        // Step 1: Run Migration
        $this->info('Step 1: Running database migration...');

        try {
            Artisan::call('migrate', [
                '--path' => 'vendor/holartweb/axora-cms/database/migrations/2026_02_27_125658_create_t_admin_actions_table.php',
                '--force' => true
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            // Try alternative path
            try {
                Artisan::call('migrate', [
                    '--path' => 'packages/holartweb/axora-cms/database/migrations/2026_02_27_125658_create_t_admin_actions_table.php',
                    '--force' => true
                ]);
                $this->info('✓ Migrations completed successfully');
            } catch (\Exception $e2) {
                $this->error('❌ Migration failed: ' . $e2->getMessage());
                return self::FAILURE;
            }
        }
        $this->newLine();

        // Step 2: Build Frontend Assets
        $this->info('Step 2: Building frontend assets...');

        if (file_exists($packagePath . '/package.json')) {
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

        // Step 3: Publish Assets
        $this->info('Step 3: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'axora-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 4: Clear Cache
        $this->info('Step 4: Clearing application cache...');
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
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ Logging Module Installed Successfully! ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now track all admin actions in your system.');
        $this->info('Navigate to: ' . url('/admin/logs'));
        $this->newLine();

        return self::SUCCESS;
    }
}
