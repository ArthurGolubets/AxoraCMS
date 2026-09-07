<?php

namespace HolartWeb\AxoraCMS;

use HolartWeb\AxoraCMS\Console\CallbackInstallCommand;
use HolartWeb\AxoraCMS\Console\CallbackUninstallCommand;
use HolartWeb\AxoraCMS\Console\CleanOldPageVisitsCommand;
use HolartWeb\AxoraCMS\Console\CommerceInstallCommand;
use HolartWeb\AxoraCMS\Console\CommerceMLInstallCommand;
use HolartWeb\AxoraCMS\Console\CommerceMLUninstallCommand;
use HolartWeb\AxoraCMS\Console\CommerceUninstallCommand;
use HolartWeb\AxoraCMS\Console\InfoBlocksInstallCommand;
use HolartWeb\AxoraCMS\Console\InfoBlocksUninstallCommand;
use HolartWeb\AxoraCMS\Console\InstallCommand;
use HolartWeb\AxoraCMS\Console\LoggingInstallCommand;
use HolartWeb\AxoraCMS\Console\LoggingUninstallCommand;
use HolartWeb\AxoraCMS\Console\PageBuilderInstallCommand;
use HolartWeb\AxoraCMS\Console\PageBuilderUninstallCommand;
use HolartWeb\AxoraCMS\Console\PagesInstallCommand;
use HolartWeb\AxoraCMS\Console\PagesUninstallCommand;
use HolartWeb\AxoraCMS\Console\ScanRoutesCommand;
use HolartWeb\AxoraCMS\Console\SeoInstallCommand;
use HolartWeb\AxoraCMS\Console\SeoUninstallCommand;
use HolartWeb\AxoraCMS\Console\ShopInstallCommand;
use HolartWeb\AxoraCMS\Console\ShopUninstallCommand;
use HolartWeb\AxoraCMS\Console\TelegramInstallCommand;
use HolartWeb\AxoraCMS\Console\TelegramUninstallCommand;
use HolartWeb\AxoraCMS\Console\UpdateCommand;
use HolartWeb\AxoraCMS\Console\YKassaCheckPaymentCommand;
use HolartWeb\AxoraCMS\Console\YookassaInstallCommand;
use HolartWeb\AxoraCMS\Console\YookassaUninstallCommand;
use HolartWeb\AxoraCMS\Http\Middleware\CheckAdminRole;
use HolartWeb\AxoraCMS\Http\Middleware\RedirectIfNotAdmin;
use HolartWeb\AxoraCMS\Http\Middleware\SharePageData;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Services\CatalogService;
use HolartWeb\AxoraCMS\Services\CommentsService;
use HolartWeb\AxoraCMS\Services\Mail\MailSettingsService;
use HolartWeb\AxoraCMS\Services\PageDataService;
use HolartWeb\AxoraCMS\Services\PageVisitService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AxoraCMSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__.'/../config/axora-cms.php', 'axora-cms'
        );

        // Register admin guard
        $this->app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'administrators',
        ]);

        $this->app['config']->set('auth.providers.administrators', [
            'driver' => 'eloquent',
            'model' => TAdministrator::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            // Migrations are NOT loaded automatically
            // They will be run during module installation via Install commands

            // Load views
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'axora-cms');

            // Register services as singletons (lazy loaded)
            $this->app->singleton(PageDataService::class, function ($app) {
                return new PageDataService;
            });

            $this->app->singleton(PageVisitService::class, function ($app) {
                return new PageVisitService;
            });

            $this->app->singleton(CatalogService::class, function ($app) {
                return new CatalogService;
            });

            $this->app->singleton(CommentsService::class, function ($app) {
                return new CommentsService;
            });

            $this->app->singleton(MailSettingsService::class, function ($app) {
                return new MailSettingsService;
            });

            // Register middleware aliases only
            $this->app['router']->aliasMiddleware('admin.auth', RedirectIfNotAdmin::class);
            $this->app['router']->aliasMiddleware('admin.role', CheckAdminRole::class);
            $this->app['router']->aliasMiddleware('share.page.data', SharePageData::class);

            // Rate limiter for admin authentication (login / password reset).
            RateLimiter::for('admin-login', function (Request $request) {
                $key = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

                return [
                    Limit::perMinute(5)->by($key),
                    Limit::perMinute(20)->by($request->ip()),
                ];
            });

            // SharePageData middleware is NOT registered automatically
            // It will be registered during module installation via InstallCommand

            // Register commands (always register so they can be called via Artisan::call() from web)
            $this->commands([
                InstallCommand::class,
                UpdateCommand::class,
                ShopInstallCommand::class,
                ShopUninstallCommand::class,
                CallbackInstallCommand::class,
                CallbackUninstallCommand::class,
                CommerceInstallCommand::class,
                CommerceUninstallCommand::class,
                LoggingInstallCommand::class,
                LoggingUninstallCommand::class,
                InfoBlocksInstallCommand::class,
                InfoBlocksUninstallCommand::class,
                PagesInstallCommand::class,
                PagesUninstallCommand::class,
                SeoInstallCommand::class,
                SeoUninstallCommand::class,
                PageBuilderInstallCommand::class,
                PageBuilderUninstallCommand::class,
                CommerceMLInstallCommand::class,
                CommerceMLUninstallCommand::class,
                ScanRoutesCommand::class,
                CleanOldPageVisitsCommand::class,
                TelegramInstallCommand::class,
                TelegramUninstallCommand::class,
                YookassaInstallCommand::class,
                YookassaUninstallCommand::class,
                YKassaCheckPaymentCommand::class,
            ]);

            // Schedule automatic cleanup of old page visits
            // Scheduled tasks will check module installation themselves
            if ($this->app->runningInConsole()) {
                $this->app->booted(function () {
                    $schedule = $this->app->make(Schedule::class);

                    // These commands handle DB checks internally
                    $schedule->command('axoracms:clean-page-visits')->daily();
                    $schedule->command('axoracms:ykassa-check-payment')->everyMinute();
                });
            }

            // Publish config
            $this->publishes([
                __DIR__.'/../config/axora-cms.php' => config_path('axora-cms.php'),
            ], 'axora-cms-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'axora-cms-migrations');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/axora-cms'),
            ], 'axora-cms-views');

            // Publish assets
            $this->publishes([
                __DIR__.'/../resources/dist' => public_path('vendor/axora-cms'),
            ], 'axora-cms-assets');

            // Register admin routes with prefix
            $this->registerAdminRoutes();

            // Register API routes (for 1C integration, etc.)
            $this->registerApiRoutes();

            // Apply runtime SMTP settings stored in the database. Wrapped so a
            // fresh install / missing table / console migration never breaks boot.
            $this->applyMailSettings();
        } catch (\Exception $e) {
            // Suppress errors during package discovery when DB is not configured
            // This allows composer require to work without database connection
        }
    }

    /**
     * Apply database-stored SMTP settings to the runtime mail configuration.
     */
    protected function applyMailSettings(): void
    {
        try {
            if (! Schema::hasTable('t_integration_settings')) {
                return;
            }

            $this->app->make(MailSettingsService::class)->apply();
        } catch (\Throwable $e) {
            // Never let mail configuration break application boot.
        }
    }

    /**
     * Register admin routes.
     */
    protected function registerAdminRoutes(): void
    {
        Route::group([
            'prefix' => config('axora-cms.route_prefix', 'admin'),
            'middleware' => ['web'],
            'namespace' => 'HolartWeb\AxoraCMS\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        });
    }

    /**
     * Register API routes (public).
     */
    protected function registerApiRoutes(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => ['web'],
            'namespace' => 'HolartWeb\AxoraCMS\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
