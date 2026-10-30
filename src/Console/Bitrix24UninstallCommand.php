<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Bitrix24UninstallCommand extends Command
{
    const MODULE_NAME = 'bitrix24';

    protected $signature = 'axoracms:bitrix24-uninstall {--preserve-db : Preserve database tables and data}';

    protected $description = 'Uninstall Bitrix24 Integration';

    public function handle(): int
    {
        if (! $this->option('preserve-db')) {
            Schema::dropIfExists('t_bitrix24_logs');
            Schema::dropIfExists('t_bitrix24_rules');

            DB::table('t_integration_settings')->where('integration_type', self::MODULE_NAME)->delete();

            $migrations = array_map(
                fn ($file) => basename($file, '.php'),
                glob(dirname(__DIR__, 2).'/database/migrations/bitrix24/*.php') ?: []
            );
            DB::table('migrations')->whereIn('migration', $migrations)->delete();
            $this->info('✓ Webhook, rules and log removed');
        }

        Artisan::call('route:clear');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        return self::SUCCESS;
    }
}
