<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;

class YookassaInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'yookassa';

    protected $signature = 'axoracms:yookassa-install';
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
            $this->error('Please install Commerce module first: php artisan axoracms:commerce-install');
            return self::FAILURE;
        }
        $this->info('✓ Commerce module is installed');
        $this->newLine();

        // Step 1: Check/Install Yookassa SDK
        $this->info('Step 1: Checking Yookassa SDK...');

        // Check if Yookassa SDK is already installed
        if (class_exists('\YooKassa\Client')) {
            $this->info('✓ Yookassa SDK is already installed');
        } else {
            $this->info('Installing Yookassa SDK via Composer...');
            $this->info('Running: composer require yoomoney/yookassa-sdk-php');

            // Try to find composer executable
            $composerPaths = [
                base_path('composer.phar'),
                '/usr/local/bin/composer',
                '/usr/bin/composer',
            ];

            $composerCmd = null;
            foreach ($composerPaths as $path) {
                if (file_exists($path)) {
                    $composerCmd = PHP_BINARY . ' ' . $path;
                    break;
                }
            }

            // If composer not found in common paths, try 'composer' command
            if (!$composerCmd) {
                exec('which composer 2>/dev/null', $output, $returnVar);
                if ($returnVar === 0 && !empty($output[0])) {
                    $composerCmd = 'composer';
                }
            }

            if (!$composerCmd) {
                $this->warn('⚠ Composer not found. Please install Yookassa SDK manually:');
                $this->warn('  composer require yoomoney/yookassa-sdk-php');
                $this->newLine();
            } else {
                try {
                    $process = proc_open(
                        $composerCmd . ' require yoomoney/yookassa-sdk-php --no-interaction',
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
                            $this->warn('⚠ Failed to install Yookassa SDK automatically');
                            $this->warn('Please install it manually: composer require yoomoney/yookassa-sdk-php');
                            $this->newLine();
                        } else {
                            $this->info('✓ Yookassa SDK installed successfully');
                        }
                    } else {
                        $this->warn('⚠ Failed to run composer');
                        $this->warn('Please install Yookassa SDK manually: composer require yoomoney/yookassa-sdk-php');
                        $this->newLine();
                    }
                } catch (\Exception $e) {
                    $this->warn('⚠ Error installing Yookassa SDK: ' . $e->getMessage());
                    $this->warn('Please install it manually: composer require yoomoney/yookassa-sdk-php');
                    $this->newLine();
                }
            }
        }
        $this->newLine();

        // Step 2: Run Migrations
        $this->info('Step 2: Running database migrations...');

        $packagePath = base_path('vendor/holartweb/axora-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/axora-cms');
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
