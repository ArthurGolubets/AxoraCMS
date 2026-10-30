<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CdekUninstallCommand extends Command
{
    const MODULE_NAME = 'cdek';

    protected $signature = 'axoracms:cdek-uninstall {--preserve-db : Preserve database tables and data}';

    protected $description = 'Uninstall CDEK Integration';

    public function handle(): int
    {
        if (! $this->option('preserve-db')) {
            Schema::dropIfExists('t_cdek_shipments');

            DB::table('t_integration_settings')->where('integration_type', self::MODULE_NAME)->delete();

            if (Schema::hasTable('t_orders_data')) {
                DB::table('t_orders_data')->where('key', CdekShipments::ENABLED_SETTING)->delete();
            }

            $migrations = array_map(
                fn ($file) => basename($file, '.php'),
                glob(dirname(__DIR__, 2).'/database/migrations/cdek/*.php') ?: []
            );
            DB::table('migrations')->whereIn('migration', $migrations)->delete();
            $this->info('✓ Account, shipments and settings removed');
        }

        Artisan::call('route:clear');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        return self::SUCCESS;
    }
}
