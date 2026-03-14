<?php

namespace HolartWeb\AxoraCMS;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
            'model' => \HolartWeb\AxoraCMS\Models\TAdministrator::class,
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
            $this->app->singleton(\HolartWeb\AxoraCMS\Services\PageDataService::class, function ($app) {
                return new \HolartWeb\AxoraCMS\Services\PageDataService();
            });

            $this->app->singleton(\HolartWeb\AxoraCMS\Services\PageVisitService::class, function ($app) {
                return new \HolartWeb\AxoraCMS\Services\PageVisitService();
            });

            $this->app->singleton(\HolartWeb\AxoraCMS\Services\CatalogService::class, function ($app) {
                return new \HolartWeb\AxoraCMS\Services\CatalogService();
            });

            $this->app->singleton(\HolartWeb\AxoraCMS\Services\CommentsService::class, function ($app) {
                return new \HolartWeb\AxoraCMS\Services\CommentsService();
            });

            // Register middleware aliases only
            $this->app['router']->aliasMiddleware('admin.auth', \HolartWeb\AxoraCMS\Http\Middleware\RedirectIfNotAdmin::class);
            $this->app['router']->aliasMiddleware('share.page.data', \HolartWeb\AxoraCMS\Http\Middleware\SharePageData::class);

            // SharePageData middleware is NOT registered automatically
            // It will be registered during module installation via InstallCommand

            // Register commands (always register so they can be called via Artisan::call() from web)
            $this->commands([
            \HolartWeb\AxoraCMS\Console\InstallCommand::class,
            \HolartWeb\AxoraCMS\Console\UpdateCommand::class,
            \HolartWeb\AxoraCMS\Console\ShopInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\ShopUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\CallbackInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\CallbackUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\CommerceInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\CommerceUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\LoggingInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\LoggingUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\InfoBlocksInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\InfoBlocksUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\PagesInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\PagesUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\SeoInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\SeoUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\PageBuilderInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\PageBuilderUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\ScanRoutesCommand::class,
            \HolartWeb\AxoraCMS\Console\CleanOldPageVisitsCommand::class,
            \HolartWeb\AxoraCMS\Console\TelegramInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\TelegramUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\YookassaInstallCommand::class,
            \HolartWeb\AxoraCMS\Console\YookassaUninstallCommand::class,
            \HolartWeb\AxoraCMS\Console\YKassaCheckPaymentCommand::class,
        ]);

        // Schedule automatic cleanup of old page visits
        // Scheduled tasks will check module installation themselves
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

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
        } catch (\Exception $e) {
            // Suppress errors during package discovery when DB is not configured
            // This allows composer require to work without database connection
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
}
