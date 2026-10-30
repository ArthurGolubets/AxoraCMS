<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageManagerSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Installs (or updates) the "Менеджер сообщений" module. Works with any set of
 * modules: events of missing modules are simply not offered.
 */
class MessageManagerInstallCommand extends Command
{
    const VERSION = '1.0.0';

    const MODULE_NAME = 'messagemanager';

    protected $signature = 'axoracms:messagemanager-install';

    protected $description = 'Install AxoraCMS Message Manager Module';

    public function handle(): int
    {
        $this->info('AxoraCMS Message Manager Module Installer');
        $this->newLine();

        $this->info('Step 1: Running database migrations...');
        try {
            // Absolute path + --realpath so it also works on Windows.
            Artisan::call('migrate', [
                '--path' => dirname(__DIR__, 2).'/database/migrations/messagemanager',
                '--realpath' => true,
                '--force' => true,
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        Artisan::call('route:clear');

        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');

        $this->info('Step 2: Creating starting templates and subscriptions...');
        (new MessageManagerSeeder)->seed();
        $this->info('✓ Done');

        if (TModule::isInstalled('telegram')) {
            $this->warn('Встроенная отправка заказов и оплат в Telegram отключена — теперь это подписки на события.');
        }
        $this->warn('Сообщения с задержкой отправляются через очередь: запустите `php artisan queue:work`.');

        return self::SUCCESS;
    }
}
