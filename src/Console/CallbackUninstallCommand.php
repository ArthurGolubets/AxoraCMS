<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CallbackUninstallCommand extends Command
{
    const MODULE_NAME = 'callback';

    protected $signature = 'holartcms:callback-user-uninstall {--preserve-db : Preserve database tables and data}';
    protected $description = 'Uninstall HolartCMS Callback Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║  HolartCMS Callback Module Uninstaller  ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if ($preserveDb) {
            $this->warn('⚠ Database tables will be preserved');
        } else {
            $this->warn('⚠ Database tables will be DELETED');
        }
        $this->newLine();

        // Step 1: Remove Models
        $this->info('Step 1: Removing callback models...');
        $models = ['TUsersEmails.php', 'TComments.php', 'TUserRequests.php'];
        foreach ($models as $model) {
            $path = app_path('Models/' . $model);
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$model}");
            }
        }
        $this->newLine();

        // Step 2: Remove Controllers
        $this->info('Step 2: Removing callback controllers...');
        $controllers = ['UsersEmailsController.php', 'CommentsController.php', 'UserRequestsController.php'];
        foreach ($controllers as $controller) {
            $path = app_path('Http/Controllers/' . $controller);
            if (File::exists($path)) {
                File::delete($path);
                $this->info("✓ Removed {$controller}");
            }
        }
        $this->newLine();

        // Step 3: Drop Tables or Keep Database
        if (!$preserveDb) {
            $this->info('Step 3: Dropping database tables...');
            $tables = ['t_users_emails', 't_comments', 't_user_requests'];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::dropIfExists($table);
                    $this->info("✓ Dropped table {$table}");
                }
            }
        } else {
            $this->info('Step 3: Skipping database drop (preserve-db flag set)');
        }
        $this->newLine();

        // Step 4: Remove Migration Records from Database
        if (!$preserveDb) {
            $this->info('Step 4: Removing migration records from database...');
            $migrationFiles = [
                '2024_01_01_000020_create_t_users_emails_table.php',
                '2024_01_01_000021_create_t_comments_table.php',
                '2024_01_01_000022_create_t_user_requests_table.php',
            ];

            try {
                DB::table('migrations')->whereIn('migration', array_map(function($file) {
                    return str_replace('.php', '', $file);
                }, $migrationFiles))->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 5: Remove Migration Files
            $this->info('Step 5: Removing migration files...');
            foreach ($migrationFiles as $file) {
                $path = database_path('migrations/' . $file);
                if (File::exists($path)) {
                    File::delete($path);
                    $this->info("✓ Removed migration {$file}");
                }
            }
        } else {
            $this->info('Step 4: Skipping migration removal (preserve-db flag set)');
        }
        $this->newLine();

        // Step 6: Clear Cache
        $this->info('Step 6: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Remove module record from database
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
        $this->newLine();

        // Success Message
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║ Callback Module Uninstalled Successfully! ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->info('Database tables were preserved and can be reused if you reinstall the module.');
        } else {
            $this->warn('All callback data has been permanently deleted.');
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
