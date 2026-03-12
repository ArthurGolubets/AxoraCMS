<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TelegramUninstallCommand extends Command
{
    const MODULE_NAME = 'telegram';

    protected $signature = 'holartcms:telegram-uninstall {--preserve-db : Preserve database tables}';
    protected $description = 'Uninstall Telegram Integration';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ Telegram Integration Uninstaller    ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->info('Removing Telegram settings from database...');

            try {
                DB::table('t_integration_settings')
                    ->where('integration_type', 'telegram')
                    ->delete();

                $this->info('✓ Telegram settings removed');
            } catch (\Exception $e) {
                $this->error('❌ Failed to remove settings: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info('✓ Database preserved');
        }

        // Remove module record
        $this->newLine();
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        $this->newLine();
        $this->info('✓ Telegram integration uninstalled successfully!');

        return self::SUCCESS;
    }
}
