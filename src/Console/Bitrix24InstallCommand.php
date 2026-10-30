<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Bitrix24InstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'bitrix24';

    protected $signature = 'axoracms:bitrix24-install';

    protected $description = 'Install Bitrix24 Integration';

    public function handle(): int
    {
        $packagePath = dirname(__DIR__, 2);

        try {
            // t_integration_settings (shared with the other integrations) holds the webhook.
            foreach (['integrations', 'bitrix24'] as $folder) {
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

        Artisan::call('route:clear');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Bitrix24 integration installed successfully!');

        return self::SUCCESS;
    }
}
