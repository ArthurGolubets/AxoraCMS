<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Enums\AdminRole;
use HolartWeb\AxoraCMS\Services\LicenseService;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'axoracms:install {--force : Force installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install AxoraCMS admin panel';

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
        $this->info('🚀 Установка AxoraCMS...');
        $this->newLine();

        // Check license
        if (!$this->checkLicense()) {
            $this->error('❌ Установка отменена: недействительный лицензионный ключ');
            return self::FAILURE;
        }

        $this->newLine();

        // Publish configuration
        $this->info('📦 Публикация конфигурации...');
        $this->call('vendor:publish', [
            '--tag' => 'axora-cms-config',
            '--force' => $this->option('force'),
        ]);

        // Run core migrations only (administrators, settings, modules)
        $this->info('🗄️  Запуск базовых миграций...');

        // Determine package path
        $packagePath = dirname(__DIR__, 2); // Go up from src/ to package root
        $migrationPath = $packagePath . '/database/migrations';

        $this->line("Migration path: {$migrationPath}");

        if (!file_exists($migrationPath)) {
            $this->error('❌ Директория миграций не найдена: ' . $migrationPath);
            return self::FAILURE;
        }

        // Run only core migrations (not module-specific subdirectories)
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--path' => str_replace(base_path() . '/', '', $migrationPath),
                '--force' => true
            ]);
            $this->info('✓ Базовые миграции завершены');
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при выполнении миграций: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Build frontend
        $this->info('🎨 Сборка фронтенда...');
        $this->buildFrontend();

        // Publish assets
        $this->info('📂 Публикация ассетов...');
        $this->call('vendor:publish', [
            '--tag' => 'axora-cms-assets',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();

        // Create super admin
        if ($this->confirm('Создать супер-администратора?', true)) {
            $this->createSuperAdmin();
        }

        $this->newLine();
        $this->info('✅ AxoraCMS успешно установлен!');
        $this->newLine();
        $this->line('Админ-панель доступна по адресу: ' . url(config('axora-cms.route_prefix', 'admin')));

        return self::SUCCESS;
    }

    /**
     * Build frontend assets
     */
    protected function buildFrontend(): void
    {
        $packagePath = dirname(__DIR__, 2);

        // Check if node_modules exists
        if (!File::exists($packagePath . '/node_modules')) {
            $this->warn('📥 Установка npm зависимостей...');
            exec("cd {$packagePath} && npm install 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('Ошибка при установке npm зависимостей');
                $this->line('Попробуйте вручную: cd ' . $packagePath . ' && npm install');
                return;
            }
        }

        // Build assets
        $this->line('🔨 Сборка ассетов...');
        exec("cd {$packagePath} && npm run build 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✅ Фронтенд успешно собран!');
        } else {
            $this->error('Ошибка при сборке фронтенда');
            $this->line('Попробуйте вручную: cd ' . $packagePath . ' && npm run build');
        }
    }

    /**
     * Check license key.
     */
    protected function checkLicense(): bool
    {
        $this->info('🔑 Проверка лицензионного ключа...');

        // Check if license already saved
        $savedKey = $this->licenseService->getSavedLicense();
        if ($savedKey && $this->licenseService->checkLicense($savedKey, 'install')) {
            $this->info('✅ Лицензия действительна');
            return true;
        }

        // Ask for license key
        $this->warn('Для установки AxoraCMS требуется лицензионный ключ');
        $key = $this->ask('Введите лицензионный ключ');

        if (!$key) {
            return false;
        }

        $this->line('Проверка ключа...');

        if (!$this->licenseService->checkLicense($key, 'install')) {
            $this->error('❌ Недействительный лицензионный ключ');
            return false;
        }

        // Save license key
        $this->licenseService->saveLicense($key);
        $this->info('✅ Лицензия активирована успешно');

        return true;
    }

    /**
     * Create super admin user
     */
    protected function createSuperAdmin(): void
    {
        $this->info('👤 Создание супер-администратора...');

        $name = $this->ask('Имя', 'Супер Администратор');
        $email = $this->ask('Email', 'admin@axoracms.local');
        $password = $this->secret('Пароль (минимум 8 символов)');
        $passwordConfirmation = $this->secret('Подтвердите пароль');

        if ($password !== $passwordConfirmation) {
            $this->error('❌ Пароли не совпадают!');
            return;
        }

        if (strlen($password) < 8) {
            $this->error('❌ Пароль должен содержать минимум 8 символов!');
            return;
        }

        // Check if email exists
        if (TAdministrator::where('email', $email)->exists()) {
            $this->error('❌ Пользователь с таким email уже существует!');
            return;
        }

        try {
            TAdministrator::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'role' => AdminRole::SUPER_ADMIN->value,
                'is_active' => true,
            ]);

            $this->info('✅ Супер-администратор успешно создан!');
            $this->table(
                ['Имя', 'Email', 'Роль'],
                [[$name, $email, 'Супер Администратор']]
            );
        } catch (\Exception $e) {
            $this->error('❌ Ошибка создания администратора: ' . $e->getMessage());
        }
    }
}
