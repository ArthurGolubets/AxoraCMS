<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class TelegramInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'telegram';

    protected $signature = 'axoracms:telegram-install';
    protected $description = 'Install Telegram Integration';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║  Telegram Integration Installer     ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // Run Migrations
        $this->info('Running database migrations...');

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('plugins/axora');
        }
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        try {
            $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/integrations';

            // Check if migration path exists
            if (!file_exists(base_path($migrationsPath))) {
                $this->warn('⚠ Migration path does not exist: ' . base_path($migrationsPath));
                $this->warn('⚠ Skipping migrations for Telegram');
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

        // Register module
        $this->newLine();
        $this->info('Registering module installation...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');

        $this->newLine();
        $this->info('✓ Telegram integration installed successfully!');
        $this->info('You can now configure Telegram settings in the admin panel.');

        return self::SUCCESS;
    }
}
