<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the "Менеджер сообщений" module. The built-in Telegram notifications
 * of orders and payments work again right after.
 */
class MessageManagerUninstallCommand extends Command
{
    const MODULE_NAME = 'messagemanager';

    protected $signature = 'axoracms:messagemanager-uninstall {--preserve-db : Preserve database tables and data}';

    protected $description = 'Uninstall AxoraCMS Message Manager Module';

    public function handle(): int
    {
        if (! $this->option('preserve-db')) {
            Schema::dropIfExists('t_message_logs');
            Schema::dropIfExists('t_message_actions');
            Schema::dropIfExists('t_message_subscriptions');
            Schema::dropIfExists('t_message_templates');

            $migrations = array_map(
                fn ($file) => basename($file, '.php'),
                glob(dirname(__DIR__, 2).'/database/migrations/messagemanager/*.php') ?: []
            );
            DB::table('migrations')->whereIn('migration', $migrations)->delete();
            $this->info('✓ Templates, subscriptions and log removed');
        }

        Artisan::call('route:clear');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        return self::SUCCESS;
    }
}
