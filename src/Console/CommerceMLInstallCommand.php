<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class CommerceMLInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'commerceml';

    protected $signature = 'axoracms:commerceml-install';
    protected $description = 'Install AxoraCMS CommerceML Integration Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ CommerceML Integration Installer   ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // Check if Shop module is installed
        $this->info('Step 1: Checking dependencies...');

        if (!TModule::isInstalled('shop')) {
            $this->error('❌ Shop module must be installed first!');
            $this->error('Please install the Shop module before installing CommerceML integration.');
            return self::FAILURE;
        }
        $this->info('✓ Shop module is installed');
        $this->newLine();

        // Step 2: Run Migrations
        $this->info('Step 2: Running database migrations...');

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('plugins/axora');
        }
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        try {
            // Run commerceml module migrations from package directory
            $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/commerceml';

            // Check if migration files exist
            $fullPath = base_path($migrationsPath);
            if (!file_exists($fullPath)) {
                $this->warn('⚠ Migration path does not exist: ' . $fullPath);
                $this->warn('⚠ Skipping migrations for CommerceML');
            } else {
                Artisan::call('migrate', [
                    '--path' => $migrationsPath,
                    '--force' => true
                ]);
                $this->info('✓ Migrations completed successfully');
            }
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

        // Step 4: Register module in database
        $this->info('Step 4: Registering module...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered successfully (version ' . self::VERSION . ')');
        $this->newLine();

        // Success Message
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ CommerceML Installed Successfully!  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now configure CommerceML settings in your admin panel.');
        $this->info('Navigate to: ' . url('/admin/integrations/commerceml'));
        $this->newLine();

        return self::SUCCESS;
    }
}
