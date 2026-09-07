<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'axoracms:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить AxoraCMS: конфигурацию и пересобрать фронтенд';

    /**
     * License service instance.
     */
    protected LicenseService $licenseService;

    /**
     * Create a new command instance.
     */
    public function __construct(LicenseService $licenseService)
    {
        parent::__construct();
        $this->licenseService = $licenseService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Обновление AxoraCMS...');
        $this->newLine();

        // Check license
        if (! $this->checkLicense()) {
            $this->error('❌ Обновление отменено: недействительный лицензионный ключ');

            return self::FAILURE;
        }

        $this->newLine();

        // 1. Publish config
        $this->info('📦 Публикация конфигурации...');
        $this->call('vendor:publish', [
            '--tag' => 'axora-cms-config',
            '--force' => true,
        ]);
        $this->newLine();

        // 2. Run pending core migrations (idempotent — Laravel skips applied ones).
        //    Absolute path + --realpath so it also works on Windows.
        $this->info('🗄️  Применение новых базовых миграций...');
        try {
            Artisan::call('migrate', [
                '--path' => dirname(__DIR__, 2).'/database/migrations',
                '--realpath' => true,
                '--force' => true,
            ]);
            $out = trim(Artisan::output());
            $this->line($out !== '' ? $out : '✓ Новых миграций нет');
        } catch (\Exception $e) {
            $this->warn('⚠ Не удалось применить миграции: '.$e->getMessage());
        }
        $this->newLine();

        // 3. Build frontend
        $this->info('🎨 Сборка фронтенда...');
        $this->buildFrontend();
        $this->newLine();

        // 3. Publish assets
        $this->info('📦 Публикация assets...');
        $this->call('vendor:publish', [
            '--tag' => 'axora-cms-assets',
            '--force' => true,
        ]);
        $this->newLine();

        // 4. Clear cache
        $this->info('🧹 Очистка кэша...');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->newLine();

        $this->info('✅ AxoraCMS успешно обновлен!');

        return self::SUCCESS;
    }

    /**
     * Build the frontend assets.
     */
    protected function buildFrontend(): void
    {
        $packagePath = dirname(__DIR__, 2);

        // Check if node_modules exists
        if (! File::exists($packagePath.'/node_modules')) {
            $this->warn('📥 Установка npm зависимостей...');
            exec("cd {$packagePath} && npm install 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('❌ Ошибка при установке npm зависимостей');
                $this->line(implode("\n", $output));

                return;
            }

            $this->info('✅ npm зависимости установлены');
        }

        // Build assets
        $this->info('🔨 Сборка assets...');
        exec("cd {$packagePath} && npm run build 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('❌ Ошибка при сборке assets');
            $this->line(implode("\n", $output));

            return;
        }

        $this->info('✅ Assets собраны успешно');
    }

    /**
     * Check license key.
     */
    protected function checkLicense(): bool
    {
        $this->info('🔑 Проверка лицензии...');

        // Check saved license
        if (! $this->licenseService->hasValidLicense('update')) {
            $this->error('❌ Лицензия недействительна или отсутствует');
            $this->line('Пожалуйста, переустановите AxoraCMS с действительной лицензией');

            return false;
        }

        $this->info('✅ Лицензия действительна');

        return true;
    }
}
