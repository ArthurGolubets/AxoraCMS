<?php

namespace HolartWeb\HolartCMS;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HolartCMSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__.'/../config/holart-cms.php', 'holart-cms'
        );

        // Register admin guard
        $this->app['config']->set('auth.guards.admin', [
            'driver' => 'session',
            'provider' => 'administrators',
        ]);

        $this->app['config']->set('auth.providers.administrators', [
            'driver' => 'eloquent',
            'model' => \HolartWeb\HolartCMS\Models\TAdministrator::class,
        ]);

        // Register services only if respective modules are installed
        $this->app->booted(function () {
            try {
                // Register PageDataService and PageVisitService if SEO or Pages module is installed
                if (\HolartWeb\HolartCMS\Models\TModule::isInstalled('seo') ||
                    \HolartWeb\HolartCMS\Models\TModule::isInstalled('pages')) {
                    $this->app->singleton(\HolartWeb\HolartCMS\Services\PageDataService::class, function ($app) {
                        return new \HolartWeb\HolartCMS\Services\PageDataService();
                    });

                    $this->app->singleton(\HolartWeb\HolartCMS\Services\PageVisitService::class, function ($app) {
                        return new \HolartWeb\HolartCMS\Services\PageVisitService();
                    });
                }

                // Register CatalogService if Shop module is installed
                if (\HolartWeb\HolartCMS\Models\TModule::isInstalled('shop')) {
                    $this->app->singleton(\HolartWeb\HolartCMS\Services\CatalogService::class, function ($app) {
                        return new \HolartWeb\HolartCMS\Services\CatalogService();
                    });
                }
            } catch (\Exception $e) {
                // Skip if database is not configured yet
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'holart-cms');

        // Register middleware
        $this->app['router']->aliasMiddleware('admin.auth', \HolartWeb\HolartCMS\Http\Middleware\RedirectIfNotAdmin::class);
        $this->app['router']->aliasMiddleware('share.page.data', \HolartWeb\HolartCMS\Http\Middleware\SharePageData::class);

        // Add SharePageData middleware to web group globally
        $this->app['router']->pushMiddlewareToGroup('web', \HolartWeb\HolartCMS\Http\Middleware\SharePageData::class);

        // Register commands (always register so they can be called via Artisan::call() from web)
        $this->commands([
            \HolartWeb\HolartCMS\Console\InstallCommand::class,
            \HolartWeb\HolartCMS\Console\UpdateCommand::class,
            \HolartWeb\HolartCMS\Console\ShopInstallCommand::class,
            \HolartWeb\HolartCMS\Console\ShopUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\CallbackInstallCommand::class,
            \HolartWeb\HolartCMS\Console\CallbackUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\CommerceInstallCommand::class,
            \HolartWeb\HolartCMS\Console\CommerceUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\LoggingInstallCommand::class,
            \HolartWeb\HolartCMS\Console\LoggingUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\InfoBlocksInstallCommand::class,
            \HolartWeb\HolartCMS\Console\InfoBlocksUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\PagesInstallCommand::class,
            \HolartWeb\HolartCMS\Console\PagesUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\SeoInstallCommand::class,
            \HolartWeb\HolartCMS\Console\SeoUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\PageBuilderInstallCommand::class,
            \HolartWeb\HolartCMS\Console\PageBuilderUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\ScanRoutesCommand::class,
            \HolartWeb\HolartCMS\Console\CleanOldPageVisitsCommand::class,
            \HolartWeb\HolartCMS\Console\TelegramInstallCommand::class,
            \HolartWeb\HolartCMS\Console\TelegramUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\YookassaInstallCommand::class,
            \HolartWeb\HolartCMS\Console\YookassaUninstallCommand::class,
            \HolartWeb\HolartCMS\Console\YKassaCheckPaymentCommand::class,
        ]);

        // Schedule automatic cleanup of old page visits
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->command('holartcms:clean-page-visits')->daily();

                // Schedule YooKassa payment check if module is installed
                try {
                    if (\HolartWeb\HolartCMS\Models\TModule::isInstalled('yookassa')) {
                        $schedule->command('holartcms:ykassa-check-payment')->everyMinute();
                    }
                } catch (\Exception $e) {
                    // Skip if tables don't exist yet (during initial installation)
                }
            });
        }

        // Publish config
        $this->publishes([
            __DIR__.'/../config/holart-cms.php' => config_path('holart-cms.php'),
        ], 'holart-cms-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'holart-cms-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/holart-cms'),
        ], 'holart-cms-views');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/holart-cms'),
        ], 'holart-cms-assets');

        // Register admin routes with prefix
        $this->registerAdminRoutes();
    }

    /**
     * Register admin routes.
     */
    protected function registerAdminRoutes(): void
    {
        Route::group([
            'prefix' => config('holart-cms.route_prefix', 'admin'),
            'middleware' => ['web'],
            'namespace' => 'HolartWeb\HolartCMS\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        });
    }
}
