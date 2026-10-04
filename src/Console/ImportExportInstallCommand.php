<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Installs (or updates) the "Импорт/Экспорт" module. Requires "Каталог и товары".
 */
class ImportExportInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'importexport';

    protected $signature = 'axoracms:importexport-install';

    protected $description = 'Install AxoraCMS Import/Export Module';

    public function handle(): int
    {
        $this->info('AxoraCMS Import/Export Module Installer');
        $this->newLine();

        if (! TModule::isInstalled('shop')) {
            $this->error('❌ Сначала установите модуль «Каталог и товары»');

            return self::FAILURE;
        }

        $this->info('Step 1: Running database migrations...');
        try {
            // Absolute path + --realpath so it also works on Windows.
            Artisan::call('migrate', [
                '--path' => dirname(__DIR__, 2).'/database/migrations/importexport',
                '--realpath' => true,
                '--force' => true,
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Step 2: Clearing application cache...');
        Artisan::call('route:clear');
        $this->info('✓ Cache cleared');

        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');
        $this->newLine();
        $this->warn('Импорт и экспорт выполняются в очереди: запустите обработчик `php artisan queue:work`.');

        return self::SUCCESS;
    }
}
