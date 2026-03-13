<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use HolartWeb\AxoraCMS\Http\Controllers\Auth\LoginController;
use HolartWeb\AxoraCMS\Http\Controllers\Auth\ForgotPasswordController;
use HolartWeb\AxoraCMS\Http\Controllers\DashboardController;
use HolartWeb\AxoraCMS\Http\Controllers\AdministratorController;
use HolartWeb\AxoraCMS\Http\Controllers\SettingsController;
use HolartWeb\AxoraCMS\Http\Controllers\SearchController;
use HolartWeb\AxoraCMS\Http\Controllers\CatalogImportExportController;
use HolartWeb\AxoraCMS\Http\Controllers\ProductImportExportController;
use HolartWeb\AxoraCMS\Http\Controllers\LogsController;
use HolartWeb\AxoraCMS\Http\Controllers\ModulesController;
use HolartWeb\AxoraCMS\Http\Controllers\EnvironmentController;
use HolartWeb\AxoraCMS\Http\Controllers\ImageUploadController;
use HolartWeb\AxoraCMS\Http\Controllers\DashboardMetricsController;
use HolartWeb\AxoraCMS\Http\Controllers\DashboardWidgetsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here are the admin panel routes for AxoraCMS
|
*/

// Guest routes (not authenticated)
Route::middleware('guest:admin')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('axora-cms.login');
    Route::post('login', [LoginController::class, 'login'])->name('axora-cms.login.post');

    // Password Reset Routes
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('axora-cms.password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('axora-cms.password.email');
});

// Authenticated admin routes
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('axora-cms.dashboard');
    Route::post('logout', [LoginController::class, 'logout'])->name('axora-cms.logout');


    // API Routes
    Route::prefix('api')->group(function () {
        Route::get('me', [DashboardController::class, 'me']);
        Route::get('search', [SearchController::class, 'search']);

        // Dashboard metrics routes
        Route::get('dashboard/metrics', [DashboardMetricsController::class, 'index']);
        Route::get('dashboard/metrics/{type}', [DashboardMetricsController::class, 'show']);

        // Dashboard widgets routes
        Route::get('dashboard/widgets', [DashboardWidgetsController::class, 'index']);
        Route::get('dashboard/widgets/available-types', [DashboardWidgetsController::class, 'availableTypes']);
        Route::post('dashboard/widgets', [DashboardWidgetsController::class, 'store']);
        Route::put('dashboard/widgets/{id}', [DashboardWidgetsController::class, 'update']);
        Route::delete('dashboard/widgets/{id}', [DashboardWidgetsController::class, 'destroy']);
        Route::post('dashboard/widgets/reorder', [DashboardWidgetsController::class, 'reorder']);
        Route::post('dashboard/widgets/reset', [DashboardWidgetsController::class, 'reset']);

        // Image upload routes
        Route::post('upload/image', [ImageUploadController::class, 'upload']);
        Route::delete('upload/image', [ImageUploadController::class, 'delete']);

        Route::get('administrators', [AdministratorController::class, 'index']);
        Route::post('administrators', [AdministratorController::class, 'store']);
        Route::put('administrators/{id}', [AdministratorController::class, 'update']);
        Route::delete('administrators/{id}', [AdministratorController::class, 'destroy']);

        Route::get('settings', [SettingsController::class, 'index']);
        Route::post('settings', [SettingsController::class, 'update']);
        Route::post('settings/upload-logo', [SettingsController::class, 'uploadLogo']);
        Route::delete('settings/logo', [SettingsController::class, 'deleteLogo']);

        Route::get('environment', [EnvironmentController::class, 'index']);
        Route::post('environment', [EnvironmentController::class, 'update']);
        Route::post('environment/test-smtp', [EnvironmentController::class, 'testSmtp']);

        // Activity logs (admin actions tracking) - only if logging module is installed
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\Logging\\TAdminAction')) {
            $logsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Logging\\LogsController';
            Route::get('logs/filters', [$logsController, 'filters']);
            Route::get('logs/statistics', [$logsController, 'statistics']);
            Route::get('logs/{id}', [$logsController, 'show']);
            Route::get('logs', [$logsController, 'index']);
        } else {
            // Fallback to system logs if logging module not installed
            Route::get('logs', [LogsController::class, 'index']);
            Route::get('logs/actions', [LogsController::class, 'actions']);
            Route::get('logs/entity-types', [LogsController::class, 'entityTypes']);
        }

        Route::get('modules/status', [ModulesController::class, 'status']);
        Route::get('modules', [ModulesController::class, 'index']);
        Route::post('modules/update', [ModulesController::class, 'update']);
        Route::post('modules/{moduleId}/install', [ModulesController::class, 'install']);
        Route::post('modules/{moduleId}/update', [ModulesController::class, 'updateModule']);
        Route::post('modules/{moduleId}/uninstall', [ModulesController::class, 'uninstall']);

        // Catalog routes - only if shop module is installed
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\Shop\\TCatalog')) {
            $catalogController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Shop\\CatalogController';
            $productController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Shop\\ProductController';
            $characteristicDefinitionsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Shop\\CharacteristicDefinitionsController';

            // Characteristic Definitions routes
            Route::post('characteristic-definitions/generate-code', [$characteristicDefinitionsController, 'generateCode']);
            Route::post('characteristic-definitions/reorder', [$characteristicDefinitionsController, 'reorder']);
            Route::get('characteristic-definitions', [$characteristicDefinitionsController, 'index']);
            Route::get('characteristic-definitions/{id}', [$characteristicDefinitionsController, 'show']);
            Route::post('characteristic-definitions', [$characteristicDefinitionsController, 'store']);
            Route::put('characteristic-definitions/{id}', [$characteristicDefinitionsController, 'update']);
            Route::delete('characteristic-definitions/{id}', [$characteristicDefinitionsController, 'destroy']);

            // Catalog Import/Export routes - MUST come before generic routes
            Route::get('catalogs/import-template', [CatalogImportExportController::class, 'downloadTemplate']);
            Route::get('catalogs/export', [CatalogImportExportController::class, 'export']);
            Route::post('catalogs/import-preview', [CatalogImportExportController::class, 'previewImport']);
            Route::post('catalogs/import', [CatalogImportExportController::class, 'import']);
            Route::get('catalogs/import-progress/{importId}', [CatalogImportExportController::class, 'checkProgress']);
            Route::get('catalogs/tree', [$catalogController, 'tree']);
            Route::get('catalogs/list', [$catalogController, 'list']);

            // Product Import/Export routes - MUST come before generic routes
            Route::get('products/import-template', [ProductImportExportController::class, 'downloadTemplate']);
            Route::get('products/export', [ProductImportExportController::class, 'export']);
            Route::post('products/import-preview', [ProductImportExportController::class, 'previewImport']);
            Route::post('products/import', [ProductImportExportController::class, 'import']);
            Route::get('products/import-progress/{importId}', [ProductImportExportController::class, 'checkProgress']);
            Route::post('products/bulk-delete', [$productController, 'bulkDestroy']);

            // Catalog generic routes
            Route::get('catalogs', [$catalogController, 'index']);
            Route::get('catalogs/{id}', [$catalogController, 'show']);
            Route::post('catalogs', [$catalogController, 'store']);
            Route::put('catalogs/{id}', [$catalogController, 'update']);
            Route::delete('catalogs/{id}', [$catalogController, 'destroy']);
            Route::get('catalogs/{id}/children', [$catalogController, 'children']);

            // Product generic routes
            Route::get('products', [$productController, 'index']);
            Route::get('products/{id}', [$productController, 'show']);
            Route::post('products', [$productController, 'store']);
            Route::put('products/{id}', [$productController, 'update']);
            Route::delete('products/{id}', [$productController, 'destroy']);
        }

        // Callback routes - only if callback module is installed
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\Callback\\TUsersEmails')) {
            $usersEmailsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Callback\\UsersEmailsController';
            $commentsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Callback\\CommentsController';
            $userRequestsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Callback\\UserRequestsController';

            // Users Emails routes
            Route::get('users-emails', [$usersEmailsController, 'index']);
            Route::get('users-emails/{id}', [$usersEmailsController, 'show']);
            Route::post('users-emails', [$usersEmailsController, 'store']);
            Route::put('users-emails/{id}', [$usersEmailsController, 'update']);
            Route::delete('users-emails/{id}', [$usersEmailsController, 'destroy']);
            Route::post('users-emails/bulk-delete', [$usersEmailsController, 'bulkDestroy']);

            // Comments routes
            Route::get('comments', [$commentsController, 'index']);
            Route::get('comments/{id}', [$commentsController, 'show']);
            Route::post('comments', [$commentsController, 'store']);
            Route::put('comments/{id}', [$commentsController, 'update']);
            Route::delete('comments/{id}', [$commentsController, 'destroy']);
            Route::post('comments/bulk-delete', [$commentsController, 'bulkDestroy']);
            Route::post('comments/{id}/toggle-moderation', [$commentsController, 'toggleModeration']);

            // User Requests routes
            Route::get('user-requests', [$userRequestsController, 'index']);
            Route::get('user-requests/{id}', [$userRequestsController, 'show']);
            Route::post('user-requests', [$userRequestsController, 'store']);
            Route::put('user-requests/{id}', [$userRequestsController, 'update']);
            Route::delete('user-requests/{id}', [$userRequestsController, 'destroy']);
            Route::post('user-requests/bulk-delete', [$userRequestsController, 'bulkDestroy']);
        }

        // Commerce routes - only if commerce module is installed
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\Commerce\\TOrders')) {
            $ordersController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Commerce\\OrdersController';
            $promocodesController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Commerce\\PromocodesController';
            $transactionsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Commerce\\PaymentTransactionsController';
            $ordersDataController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Commerce\\OrdersDataController';

            // Orders routes
            Route::get('orders', [$ordersController, 'index']);
            Route::get('orders/{id}', [$ordersController, 'show']);
            Route::post('orders', [$ordersController, 'store']);
            Route::put('orders/{id}', [$ordersController, 'update']);
            Route::delete('orders/{id}', [$ordersController, 'destroy']);
            Route::put('orders/{id}/status', [$ordersController, 'updateStatus']);

            // Promocodes routes
            Route::get('promocodes', [$promocodesController, 'index']);
            Route::get('promocodes/{id}', [$promocodesController, 'show']);
            Route::post('promocodes', [$promocodesController, 'store']);
            Route::put('promocodes/{id}', [$promocodesController, 'update']);
            Route::delete('promocodes/{id}', [$promocodesController, 'destroy']);
            Route::post('promocodes/verify', [$promocodesController, 'verify']);

            // Payment Transactions routes
            Route::get('transactions', [$transactionsController, 'index']);
            Route::get('transactions/{id}', [$transactionsController, 'show']);
            Route::post('transactions', [$transactionsController, 'store']);
            Route::put('transactions/{id}', [$transactionsController, 'update']);
            Route::delete('transactions/{id}', [$transactionsController, 'destroy']);

            // Orders Settings routes
            Route::get('orders-settings', [$ordersDataController, 'index']);
            Route::get('orders-settings/{key}', [$ordersDataController, 'show']);
            Route::post('orders-settings', [$ordersDataController, 'store']);
            Route::put('orders-settings/{key}', [$ordersDataController, 'update']);
            Route::delete('orders-settings/{key}', [$ordersDataController, 'destroy']);
        }

        // InfoBlocks routes - only if infoblocks module is installed
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\InfoBlocks\\TInfoBlock')) {
            $infoBlocksController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\InfoBlocks\\InfoBlocksController';
            $infoBlockFieldsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\InfoBlocks\\InfoBlockFieldsController';
            $infoBlockElementsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\InfoBlocks\\InfoBlockElementsController';

            // InfoBlocks routes
            Route::get('infoblocks/favorites', [$infoBlocksController, 'favorites']);
            Route::get('infoblocks', [$infoBlocksController, 'index']);
            Route::get('infoblocks/{id}', [$infoBlocksController, 'show']);
            Route::post('infoblocks', [$infoBlocksController, 'store']);
            Route::put('infoblocks/{id}', [$infoBlocksController, 'update']);
            Route::delete('infoblocks/{id}', [$infoBlocksController, 'destroy']);
            Route::post('infoblocks/{id}/favorite', [$infoBlocksController, 'toggleFavorite']);

            // InfoBlock Fields routes
            Route::get('infoblocks/{infoBlockId}/fields', [$infoBlockFieldsController, 'index']);
            Route::get('infoblocks/{infoBlockId}/fields/{id}', [$infoBlockFieldsController, 'show']);
            Route::post('infoblocks/{infoBlockId}/fields', [$infoBlockFieldsController, 'store']);
            Route::put('infoblocks/{infoBlockId}/fields/{id}', [$infoBlockFieldsController, 'update']);
            Route::delete('infoblocks/{infoBlockId}/fields/{id}', [$infoBlockFieldsController, 'destroy']);
            Route::get('infoblocks/field-types', [$infoBlockFieldsController, 'types']);

            // InfoBlock Elements routes
            Route::get('infoblocks/{infoBlockId}/elements', [$infoBlockElementsController, 'index']);
            Route::get('infoblocks/{infoBlockId}/elements/{id}', [$infoBlockElementsController, 'show']);
            Route::post('infoblocks/{infoBlockId}/elements', [$infoBlockElementsController, 'store']);
            Route::put('infoblocks/{infoBlockId}/elements/{id}', [$infoBlockElementsController, 'update']);
            Route::delete('infoblocks/{infoBlockId}/elements/{id}', [$infoBlockElementsController, 'destroy']);
        }


        if (class_exists('\HolartWeb\AxoraCMS\Http\Controllers\Menus\MenuItemsController') && class_exists('\HolartWeb\AxoraCMS\Http\Controllers\Menus\MenusController')) {
            $menusController = \HolartWeb\AxoraCMS\Http\Controllers\Menus\MenusController::class;
            $menuItemsController = \HolartWeb\AxoraCMS\Http\Controllers\Menus\MenuItemsController::class;

            // Menus routes
            Route::get('menus', [$menusController, 'index']);
            Route::get('menus/{id}', [$menusController, 'show']);
            Route::post('menus', [$menusController, 'store']);
            Route::put('menus/{id}', [$menusController, 'update']);
            Route::delete('menus/{id}', [$menusController, 'destroy']);
            Route::post('menus/{id}/toggle-active', [$menusController, 'toggleActive']);
            Route::post('menus/generate-code', [$menusController, 'generateCode']);

            // Menu Items routes
            Route::get('menus/{menuId}/items', [$menuItemsController, 'index']);
            Route::post('menus/{menuId}/items', [$menuItemsController, 'store']);
            Route::post('menus/{menuId}/items/reorder', [$menuItemsController, 'reorder']);
            Route::post('menu-items', [$menuItemsController, 'store']);
            Route::put('menu-items/{id}', [$menuItemsController, 'update']);
            Route::delete('menu-items/{id}', [$menuItemsController, 'destroy']);
            Route::post('menu-items/reorder', [$menuItemsController, 'reorder']);
            Route::post('menu-items/{id}/toggle-active', [$menuItemsController, 'toggleActive']);
        }



        // Filter routes (only if shop module is installed)
        if (class_exists('HolartWeb\\AxoraCMS\\Models\\Shop\\TFilter')) {
            $filterController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Shop\\FilterController';

            // Specific routes MUST come before generic {id} routes
            Route::get('filters/for-catalog/{catalogId}', [$filterController, 'forCatalog']);
            Route::post('filters/generate-code', [$filterController, 'generateCode']);

            // Filter values routes
            Route::post('filters/{filterId}/values', [$filterController, 'addValue']);
            Route::put('filters/{filterId}/values/{valueId}', [$filterController, 'updateValue']);
            Route::delete('filters/{filterId}/values/{valueId}', [$filterController, 'deleteValue']);

            // Generic CRUD routes
            Route::get('filters', [$filterController, 'index']);
            Route::get('filters/{id}', [$filterController, 'show']);
            Route::post('filters', [$filterController, 'store']);
            Route::put('filters/{id}', [$filterController, 'update']);
            Route::delete('filters/{id}', [$filterController, 'destroy']);
        }

        // Pages & SEO Module Routes (only if SEO module is installed)
        if (Schema::hasTable('t_pages')) {
            $pagesController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\SEO\\PagesController';
            $statsController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\SEO\\PageStatsController';

            // Pages CRUD
            Route::get('pages', [$pagesController, 'index']);
            Route::get('pages/types', [$pagesController, 'getTypes']);
            Route::get('pages/{id}', [$pagesController, 'show']);
            Route::post('pages', [$pagesController, 'store']);
            Route::put('pages/{id}', [$pagesController, 'update']);
            Route::delete('pages/{id}', [$pagesController, 'destroy']);
            Route::post('pages/{id}/toggle-status', [$pagesController, 'toggleStatus']);
            Route::post('pages/scan-routes', [$pagesController, 'scanRoutes']);

            // Pages Statistics
            Route::get('page-stats', [$statsController, 'index']);
            Route::get('page-stats/chart', [$statsController, 'chart']);
            Route::get('page-stats/popular', [$statsController, 'popular']);
            Route::get('page-stats/unlinked', [$statsController, 'unlinked']);
            Route::get('page-stats/{pageId}', [$statsController, 'pageStats']);
        }

        // Integrations routes (only if integrations table exists)
        if (Schema::hasTable('t_integration_settings')) {
            $telegramController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Integrations\\TelegramSettingsController';
            $yookassaController = 'HolartWeb\\AxoraCMS\\Http\\Controllers\\Integrations\\YookassaSettingsController';

            // Telegram integration
            Route::get('integrations/telegram', [$telegramController, 'index']);
            Route::post('integrations/telegram', [$telegramController, 'update']);

            // Yookassa integration
            Route::get('integrations/yookassa', [$yookassaController, 'index']);
            Route::post('integrations/yookassa', [$yookassaController, 'update']);
        }
    });

    // SPA route - catch all for Vue Router
    Route::get('/{any}', [DashboardController::class, 'index'])
        ->where('any', '.*')
        ->name('axora-cms.spa');
});
