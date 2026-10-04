<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;

/**
 * Installs the "Менеджер цен" module (bulk price changes). Requires "Каталог и товары".
 */
class PriceManagerInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'pricemanager';

    protected $signature = 'axoracms:pricemanager-install';

    protected $description = 'Install AxoraCMS Price Manager Module';

    public function handle(): int
    {
        if (! TModule::isInstalled('shop')) {
            $this->error('❌ Сначала установите модуль «Каталог и товары»');

            return self::FAILURE;
        }

        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Модуль «Менеджер цен» установлен');

        return self::SUCCESS;
    }
}
