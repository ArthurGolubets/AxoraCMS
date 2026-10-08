<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Console\Command;

/**
 * Drops everything cached by the "Кеширование" module (run after deploys or direct DB edits).
 */
class CacheClearCommand extends Command
{
    protected $signature = 'axoracms:cache-clear';

    protected $description = 'Clear the AxoraCMS site cache';

    public function handle(SiteCacheService $siteCache): int
    {
        $siteCache->rebuildConfig();
        $siteCache->flush();

        $this->info('✓ Кеш сайта очищен');

        return self::SUCCESS;
    }
}
