<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\Commerce\TOrdersData;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CdekInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'cdek';

    protected $signature = 'axoracms:cdek-install';

    protected $description = 'Install CDEK Integration';

    public function handle(): int
    {
        if (! TModule::isInstalled('commerce')) {
            $this->error('❌ Сначала установите модуль «Коммерция»');

            return self::FAILURE;
        }

        $packagePath = dirname(__DIR__, 2);

        try {
            // t_integration_settings (shared with the other integrations) holds the account.
            foreach (['integrations', 'cdek'] as $folder) {
                Artisan::call('migrate', [
                    '--path' => $packagePath.'/database/migrations/'.$folder,
                    '--realpath' => true,
                    '--force' => true,
                ]);
            }
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (TOrdersData::getValue(CdekShipments::ENABLED_SETTING) === null) {
            TOrdersData::setValue(CdekShipments::ENABLED_SETTING, false, TOrdersData::TYPE_BOOLEAN);
        }

        Artisan::call('route:clear');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ CDEK integration installed successfully!');

        return self::SUCCESS;
    }
}
