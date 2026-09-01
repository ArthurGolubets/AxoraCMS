<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class InfoBlocksInstallCommand extends Command
{
    const VERSION = '1.2.0';
    const MODULE_NAME = 'infoblocks';

    protected $signature = 'axoracms:infoblocks-install';
    protected $description = 'Install AxoraCMS InfoBlocks Module';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║ AxoraCMS InfoBlocks Module Installer ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        // Step 1: Run Migrations
        $this->info('Step 1: Running database migrations...');

        // Determine migration path
        $migrationPath = 'vendor/holartweb/axora-cms/database/migrations/infoblocks';
        if (!file_exists(base_path($migrationPath))) {
            $migrationPath = 'packages/holartweb/axora-cms/database/migrations/infoblocks';
        }

        try {
            Artisan::call('migrate', [
                '--path' => $migrationPath,
                '--force' => true
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return self::FAILURE;
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
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║InfoBlocks Module Installed Successfully!║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now create custom info blocks in your admin panel.');
        $this->info('Navigate to: ' . url('/admin/infoblocks'));
        $this->newLine();

        return self::SUCCESS;
    }
}
