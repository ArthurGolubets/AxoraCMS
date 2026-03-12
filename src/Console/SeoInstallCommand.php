<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\HolartCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use HolartWeb\HolartCMS\Models\TAdminAction;

class SeoInstallCommand extends Command
{
    const VERSION = '1.0.0';
    const MODULE_NAME = 'seo';

    protected $signature = 'holartcms:seo-install';
    protected $description = 'Install HolartCMS SEO Module';

    public function handle(): int
    {
        $this->info('╔═══════════════════════════════════════════╗');
        $this->info('║ HolartCMS Pages & SEO Module Installer   ║');
        $this->info('╚═══════════════════════════════════════════╝');
        $this->newLine();

        // Determine package path
        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        // Step 1: Run migrations
        $this->info('Step 1: Running SEO migrations...');

        // Determine migration path
        $migrationPath = 'vendor/holartweb/holart-cms/database/migrations/seo';
        if (!file_exists(base_path($migrationPath))) {
            $migrationPath = 'packages/holartweb/holart-cms/database/migrations/seo';
        }

        Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--force' => true
        ]);
        $this->info('✓ Migrations completed');
        $this->newLine();

        // Step 2: Register middleware automatically
        $this->info('Step 2: Registering middleware...');
        $this->registerMiddleware();
        $this->info('✓ Middleware registered');
        $this->newLine();

        // Step 3: Register module installation
        $this->info('Step 3: Registering module installation...');
        TModule::install(self::MODULE_NAME, self::VERSION);
        $this->info('✓ Module registered');
        $this->newLine();

        $this->info('╔════════════════════════════════════════════════╗');
        $this->info('║ Pages & SEO Module installed successfully     ║');
        $this->info('╚════════════════════════════════════════════════╝');

        // Log activity if logging module is installed
        if (Schema::hasTable('t_admin_actions') && class_exists(TAdminAction::class)) {
            TAdminAction::log('installed', 'module', null, 'Установлен модуль: Страницы и SEO');
        }

        return Command::SUCCESS;
    }

    /**
     * Register middleware in bootstrap/app.php
     */
    private function registerMiddleware()
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapPath)) {
            $this->warn('⚠ bootstrap/app.php not found. Please register middleware manually.');
            return;
        }

        $content = file_get_contents($bootstrapPath);
        $trackPageVisitsMiddleware = '\HolartWeb\HolartCMS\Http\Middleware\TrackPageVisits::class';
        $sharePageDataMiddleware = '\HolartWeb\HolartCMS\Http\Middleware\SharePageData::class';

        // Check if already registered
        if (str_contains($content, 'TrackPageVisits') && str_contains($content, 'SharePageData')) {
            $this->info('   Middleware already registered');
            return;
        }

        // For Laravel 11+ style bootstrap/app.php
        if (str_contains($content, '->withMiddleware')) {
            // Try different patterns for withMiddleware including void return type
            $patterns = [
                '/->withMiddleware\(function\s*\(\s*Middleware\s+\$middleware\s*\)\s*:\s*void\s*{/',
                '/->withMiddleware\(function\s*\(\s*Middleware\s+\$middleware\s*\)\s*{/',
                '/->withMiddleware\(function\s*\(\s*\$middleware\s*\)\s*{/',
                '/->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*{/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $insertPosition = $matches[0][1] + strlen($matches[0][0]);

                    // Insert both middleware registrations
                    $middlewareCode = "\n        \$middleware->web(append: [\n            {$sharePageDataMiddleware},\n            {$trackPageVisitsMiddleware},\n        ]);\n";

                    $content = substr_replace($content, $middlewareCode, $insertPosition, 0);
                    file_put_contents($bootstrapPath, $content);
                    $this->info('   Middleware registered successfully');
                    return;
                }
            }
        }

        // Fallback: couldn't auto-register
        $this->warn('⚠ Could not auto-register middleware. Please add manually to bootstrap/app.php:');
        $this->warn('   $middleware->web(append: [');
        $this->warn('       ' . $sharePageDataMiddleware . ',');
        $this->warn('       ' . $trackPageVisitsMiddleware . ',');
        $this->warn('   ]);');
    }
}
