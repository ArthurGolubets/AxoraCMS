<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Console\Command;

/**
 * Removes the "Кеширование" module. Cached entries are invalidated; settings are
 * deleted unless --preserve-db is passed.
 */
class CacheUninstallCommand extends Command
{
    protected $signature = 'axoracms:cache-uninstall {--preserve-db : Keep the module settings}';

    protected $description = 'Uninstall AxoraCMS Cache Module';

    public function handle(SiteCacheService $siteCache): int
    {
        $siteCache->flush();

        if (! $this->option('preserve-db')) {
            $siteCache->removeSettings();
        }

        TModule::uninstall(SiteCacheService::MODULE_NAME);

        $this->info('✓ Модуль «Кеширование» удалён');

        return self::SUCCESS;
    }
}
