<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class PageBuilderInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'pagebuilder';

    protected $signature = 'axoracms:pagebuilder-install';

    protected $description = 'Install AxoraCMS Page Builder Module';

    public function handle(): int
    {
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║ AxoraCMS Page Builder Module Installer  ║');
        $this->info('╚═══════════════════════════════════════════╝');
        $this->newLine();

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (! file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
        }

        // Step 1: Run migrations
        $this->info('Step 1: Running Page Builder migrations...');

        // Determine migration path
        $migrationPath = 'vendor/holartweb/axora-cms/database/migrations/pages';
        if (! file_exists(base_path($migrationPath))) {
            $migrationPath = 'packages/holartweb/axora-cms/database/migrations/pages';
        }

        Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--force' => true,
        ]);
        $this->info('✓ Migrations completed');
        $this->newLine();

        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║ Page Builder Module installed successfully ║');
        $this->info('╚═══════════════════════════════════════════╝');

        // Register module
        $this->newLine();
        $this->info('Registering module installation...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');

        // Log activity if logging module is installed
        if (Schema::hasTable('t_admin_actions') && class_exists(TAdminAction::class)) {
            TAdminAction::log('installed', 'module', null, 'Установлен модуль: Конструктор страниц');
        }

        return Command::SUCCESS;
    }
}
