<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;

/**
 * Removes the "Менеджер цен" module. It has no own data, prices stay as they are.
 */
class PriceManagerUninstallCommand extends Command
{
    const MODULE_NAME = 'pricemanager';

    protected $signature = 'axoracms:pricemanager-uninstall {--preserve-db : Kept for compatibility, the module has no tables}';

    protected $description = 'Uninstall AxoraCMS Price Manager Module';

    public function handle(): int
    {
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Модуль «Менеджер цен» удалён');

        return self::SUCCESS;
    }
}
