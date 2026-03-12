<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use HolartWeb\HolartCMS\Models\TAdminAction;

class SeoUninstallCommand extends Command
{
    const MODULE_NAME = 'seo';

    protected $signature = 'holartcms:seo-uninstall {--preserve-db : Preserve database tables}';
    protected $description = 'Uninstall HolartCMS SEO Module';

    public function handle(): int
    {
        $this->info('╔═════════════════════════════════════════════╗');
        $this->info('║ HolartCMS Pages & SEO Module Uninstaller   ║');
        $this->info('╚═════════════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        // Step 1: Remove middleware from bootstrap/app.php
        $this->info('Step 1: Removing middleware from bootstrap/app.php...');
        $this->unregisterMiddleware();
        $this->newLine();

        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║ Pages & SEO Module uninstalled successfully     ║');
        $this->info('╚══════════════════════════════════════════════════╝');

        // Step 2: Remove files
        $this->info('Step 2: Removing files...');

        $filesToRemove = [
            app_path('Models/TPage.php'),
            app_path('Models/TPageVisit.php'),
            app_path('Http/Controllers/PagesController.php'),
            app_path('Http/Controllers/PageStatsController.php'),
            app_path('Services/PageVisitService.php'),
            app_path('Http/Middleware/TrackPageVisits.php'),
        ];

        foreach ($filesToRemove as $file) {
            if (file_exists($file)) {
                unlink($file);
                $this->info("✓ Removed " . basename($file));
            }
        }
        $this->newLine();

        // Step 3: Drop database tables (if not preserving)
        if (!$preserveDb) {
            $this->info('Step 3: Dropping database tables...');

            if (Schema::hasTable('t_page_visits')) {
                Schema::dropIfExists('t_page_visits');
                $this->info('✓ Dropped table: t_page_visits');
            }

            if (Schema::hasTable('t_pages')) {
                Schema::dropIfExists('t_pages');
                $this->info('✓ Dropped table: t_pages');
            }

            // Delete migration records
            DB::table('migrations')
                ->where('migration', 'like', '%create_t_pages_table%')
                ->orWhere('migration', 'like', '%create_t_page_visits_table%')
                ->delete();

            $this->newLine();
        } else {
            $this->warn('⚠ Database tables preserved (--preserve-db flag used)');
            $this->newLine();
        }



        // Log activity if logging module is installed
        if (Schema::hasTable('t_admin_actions') && class_exists(TAdminAction::class)) {
            TAdminAction::log('uninstalled', 'module', null, 'Удален модуль: Страницы и SEO');
        }

        return Command::SUCCESS;
    }

    /**
     * Remove middleware from bootstrap/app.php
     */
    private function unregisterMiddleware()
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapPath)) {
            $this->warn('⚠ bootstrap/app.php not found. Please remove middleware manually.');
            return;
        }

        $content = file_get_contents($bootstrapPath);

        // Check if middleware is registered
        if (!str_contains($content, 'TrackPageVisits')) {
            $this->info('   Middleware not found in bootstrap/app.php');
            return;
        }

        // Remove the middleware line(s)
        // Pattern 1: Remove line with TrackPageVisits::class
        $content = preg_replace(
            '/\s*\\\App\\\Http\\\Middleware\\\TrackPageVisits::class,?\s*\n/',
            '',
            $content
        );

        // Pattern 2: Remove entire $middleware->web(append: [...]) block if it only contains TrackPageVisits
        $content = preg_replace(
            '/\s*\$middleware->web\(append:\s*\[\s*\\\App\\\Http\\\Middleware\\\TrackPageVisits::class,?\s*\]\);\s*\n/',
            '',
            $content
        );

        // Clean up empty append blocks
        $content = preg_replace(
            '/\s*\$middleware->web\(append:\s*\[\s*\]\);\s*\n/',
            '',
            $content
        );

        file_put_contents($bootstrapPath, $content);
        $this->info('   Middleware removed from bootstrap/app.php');

        // Remove module record
        $this->newLine();
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
    }
}
