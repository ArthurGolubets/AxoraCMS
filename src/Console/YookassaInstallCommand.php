<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class YookassaInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'yookassa';

    protected $signature = 'holartcms:yookassa-install';
    protected $description = 'Install Yookassa Integration';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║  Yookassa Integration Installer     ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        // Check if Commerce module is installed
        $this->info('Checking dependencies...');
        if (!TModule::isInstalled('commerce')) {
            $this->error('❌ Commerce module is required for Yookassa integration!');
            $this->error('Please install Commerce module first: php artisan holartcms:commerce-install');
            return self::FAILURE;
        }
        $this->info('✓ Commerce module is installed');
        $this->newLine();

        // Step 1: Install Yookassa SDK via Composer
        $this->info('Step 1: Installing Yookassa SDK...');
        $this->info('Running: composer require yoomoney/yookassa-sdk-php');

        try {
            $process = proc_open(
                'composer require yoomoney/yookassa-sdk-php --no-interaction',
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ],
                $pipes,
                base_path()
            );

            if (is_resource($process)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnCode = proc_close($process);

                if ($returnCode !== 0) {
                    $this->error('❌ Failed to install Yookassa SDK');
                    $this->error($errors ?: $output);
                    return self::FAILURE;
                }

                $this->info('✓ Yookassa SDK installed successfully');
            } else {
                $this->error('❌ Failed to run composer');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error installing Yookassa SDK: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // Step 2: Run Migrations
        $this->info('Step 2: Running database migrations...');

        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        try {
            $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/integrations';
            Artisan::call('migrate', [
                '--path' => $migrationsPath,
                '--force' => true
            ]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Register module
        $this->newLine();
        $this->info('Registering module installation...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');

        $this->newLine();
        $this->info('✓ Yookassa integration installed successfully!');
        $this->info('You can now configure Yookassa settings in the admin panel.');

        return self::SUCCESS;
    }
}
