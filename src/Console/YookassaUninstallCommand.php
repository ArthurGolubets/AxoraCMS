<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class YookassaUninstallCommand extends Command
{
    const MODULE_NAME = 'yookassa';

    protected $signature = 'holartcms:yookassa-uninstall {--preserve-db : Preserve database tables}';
    protected $description = 'Uninstall Yookassa Integration';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ Yookassa Integration Uninstaller    ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->info('Removing Yookassa settings from database...');

            try {
                DB::table('t_integration_settings')
                    ->where('integration_type', 'yookassa')
                    ->delete();

                $this->info('✓ Yookassa settings removed');
            } catch (\Exception $e) {
                $this->error('❌ Failed to remove settings: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info('✓ Database preserved');
        }

        // Remove Yookassa SDK via Composer
        $this->newLine();
        $this->info('Removing Yookassa SDK...');
        $this->info('Running: composer remove yoomoney/yookassa-sdk-php');

        try {
            $process = proc_open(
                'composer remove yoomoney/yookassa-sdk-php --no-interaction',
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
                    $this->warn('⚠ Failed to remove Yookassa SDK');
                    $this->warn($errors ?: $output);
                } else {
                    $this->info('✓ Yookassa SDK removed successfully');
                }
            } else {
                $this->warn('⚠ Failed to run composer');
            }
        } catch (\Exception $e) {
            $this->warn('⚠ Error removing Yookassa SDK: ' . $e->getMessage());
        }

        // Remove module record
        $this->newLine();
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');

        $this->newLine();
        $this->info('✓ Yookassa integration uninstalled successfully!');

        return self::SUCCESS;
    }
}
