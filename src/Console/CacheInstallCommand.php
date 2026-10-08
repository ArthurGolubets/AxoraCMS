<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Console\Command;

/**
 * Installs the "Кеширование" module: seeds its settings and turns the site cache on.
 */
class CacheInstallCommand extends Command
{
    const VERSION = '1.0.0';

    protected $signature = 'axoracms:cache-install';

    protected $description = 'Install AxoraCMS Cache Module';

    public function handle(SiteCacheService $siteCache): int
    {
        $siteCache->seedDefaultSettings();

        TModule::install(SiteCacheService::MODULE_NAME, self::VERSION);

        $this->info('✓ Модуль «Кеширование» установлен');
        $this->line('  Хранилище кеша: '.$siteCache->storeName());

        return self::SUCCESS;
    }
}
