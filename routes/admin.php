<?php

use Illuminate\Support\Facades\Route;
use HolartWeb\HolartCMS\Http\Controllers\Auth\LoginController;
use HolartWeb\HolartCMS\Http\Controllers\Auth\ForgotPasswordController;
use HolartWeb\HolartCMS\Http\Controllers\DashboardController;
use HolartWeb\HolartCMS\Http\Controllers\AdministratorController;
use HolartWeb\HolartCMS\Http\Controllers\SettingsController;
use HolartWeb\HolartCMS\Http\Controllers\SearchController;
use HolartWeb\HolartCMS\Http\Controllers\CatalogImportExportController;
use HolartWeb\HolartCMS\Http\Controllers\ProductImportExportController;
use HolartWeb\HolartCMS\Http\Controllers\LogsController;
use HolartWeb\HolartCMS\Http\Controllers\ModulesController;
use HolartWeb\HolartCMS\Http\Controllers\EnvironmentController;
use HolartWeb\HolartCMS\Http\Controllers\ImageUploadController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here are the admin panel routes for HolartCMS
|
*/

// Guest routes (not authenticated)
Route::middleware('guest:admin')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('holart-cms.login');
    Route::post('login', [LoginController::class, 'login'])->name('holart-cms.login.post');

    // Password Reset Routes
    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('holart-cms.password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('holart-cms.password.email');
});

// Authenticated admin routes
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('holart-cms.dashboard');
    Route::post('logout', [LoginController::class, 'logout'])->name('holart-cms.logout');

    // Page Viewer Route (must be before API routes to avoid conflicts)
    Route::get('viewer/{slug}', [\HolartWeb\HolartCMS\Http\Controllers\Pages\PageViewerController::class, 'preview'])->where('slug', '.*');

    // API Routes
    Route::prefix('api')->group(function () {
        Route::get('me', [DashboardController::class, 'me']);
        Route::get('search', [SearchController::class, 'search']);

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
        $logsControllerPath = app_path('Http/Controllers/LogsController.php');
        if (file_exists($logsControllerPath)) {
            $logsController = 'App\\Http\\Controllers\\LogsController';
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

        Route::get('modules', [ModulesController::class, 'index']);
        Route::post('modules/update', [ModulesController::class, 'update']);
        Route::post('modules/{moduleId}/install', [ModulesController::class, 'install']);
        Route::post('modules/{moduleId}/uninstall', [ModulesController::class, 'uninstall']);

        // Catalog routes - only if shop module is installed
        $catalogControllerPath = app_path('Http/Controllers/CatalogController.php');
        if (file_exists($catalogControllerPath)) {
            $catalogController = 'App\\Http\\Controllers\\CatalogController';
            $productController = 'App\\Http\\Controllers\\ProductController';

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
        $usersEmailsControllerPath = app_path('Http/Controllers/UsersEmailsController.php');
        if (file_exists($usersEmailsControllerPath)) {
            $usersEmailsController = 'App\\Http\\Controllers\\UsersEmailsController';
            $commentsController = 'App\\Http\\Controllers\\CommentsController';
            $userRequestsController = 'App\\Http\\Controllers\\UserRequestsController';

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
        $ordersControllerPath = app_path('Http/Controllers/OrdersController.php');
        if (file_exists($ordersControllerPath)) {
            $ordersController = 'App\\Http\\Controllers\\OrdersController';
            $promocodesController = 'App\\Http\\Controllers\\PromocodesController';
            $transactionsController = 'App\\Http\\Controllers\\PaymentTransactionsController';
            $ordersDataController = 'App\\Http\\Controllers\\OrdersDataController';

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
        $infoBlocksControllerPath = app_path('Http/Controllers/InfoBlocksController.php');
        if (file_exists($infoBlocksControllerPath)) {
            $infoBlocksController = 'App\\Http\\Controllers\\InfoBlocksController';
            $infoBlockFieldsController = 'App\\Http\\Controllers\\InfoBlockFieldsController';
            $infoBlockElementsController = 'App\\Http\\Controllers\\InfoBlockElementsController';

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

        // Pages routes - only if pages module is installed
        $pagesControllerPath = app_path('Http/Controllers/PagesController.php');
        if (file_exists($pagesControllerPath)) {
            $pagesController = 'App\\Http\\Controllers\\PagesController';
            $pageBlocksController = 'App\\Http\\Controllers\\PageBlocksController';
            $pageBlockTypesController = 'App\\Http\\Controllers\\PageBlockTypesController';
            $pageViewerController = 'App\\Http\\Controllers\\PageViewerController';

            // Pages routes
            Route::get('pages', [$pagesController, 'index']);
            Route::get('pages/{id}', [$pagesController, 'show']);
            Route::post('pages', [$pagesController, 'store']);
            Route::put('pages/{id}', [$pagesController, 'update']);
            Route::delete('pages/{id}', [$pagesController, 'destroy']);
            Route::post('pages/{id}/duplicate', [$pagesController, 'duplicate']);
            Route::post('pages/{id}/toggle-publish', [$pagesController, 'togglePublish']);
            Route::post('pages/generate-slug', [$pagesController, 'generateSlug']);

            // Page Blocks routes
            Route::get('pages/{pageId}/blocks', [$pageBlocksController, 'index']);
            Route::get('pages/{pageId}/blocks/{id}', [$pageBlocksController, 'show']);
            Route::post('pages/{pageId}/blocks', [$pageBlocksController, 'store']);
            Route::put('pages/{pageId}/blocks/{id}', [$pageBlocksController, 'update']);
            Route::delete('pages/{pageId}/blocks/{id}', [$pageBlocksController, 'destroy']);
            Route::post('pages/{pageId}/blocks/reorder', [$pageBlocksController, 'reorder']);

            // Page Block Types routes
            Route::get('page-block-types', [$pageBlockTypesController, 'index']);
            Route::get('page-block-types/{id}', [$pageBlockTypesController, 'show']);
            Route::post('page-block-types', [$pageBlockTypesController, 'store']);
            Route::put('page-block-types/{id}', [$pageBlockTypesController, 'update']);
            Route::delete('page-block-types/{id}', [$pageBlockTypesController, 'destroy']);
            Route::post('page-block-types/create-template', [$pageBlockTypesController, 'createTemplate']);
            Route::delete('page-block-types/{id}/template', [$pageBlockTypesController, 'deleteTemplate']);
        }

        // Menus routes - use app controllers if they exist
        $menusController = class_exists(\App\Http\Controllers\MenusController::class)
            ? \App\Http\Controllers\MenusController::class
            : \HolartWeb\HolartCMS\Http\Controllers\Menus\MenusController::class;
        $menuItemsController = class_exists(\App\Http\Controllers\MenuItemsController::class)
            ? \App\Http\Controllers\MenuItemsController::class
            : \HolartWeb\HolartCMS\Http\Controllers\Menus\MenuItemsController::class;

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
        Route::post('menu-items', [$menuItemsController, 'store']);
        Route::put('menu-items/{id}', [$menuItemsController, 'update']);
        Route::delete('menu-items/{id}', [$menuItemsController, 'destroy']);
        Route::post('menu-items/reorder', [$menuItemsController, 'reorder']);
        Route::post('menu-items/{id}/toggle-active', [$menuItemsController, 'toggleActive']);

        // Filter routes (only if shop module is installed)
        $filterControllerPath = app_path('Http/Controllers/FilterController.php');
        if (file_exists($filterControllerPath)) {
            $filterController = 'App\\Http\\Controllers\\FilterController';

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
    });

    // SPA route - catch all for Vue Router
    Route::get('/{any}', [DashboardController::class, 'index'])
        ->where('any', '.*')
        ->name('holart-cms.spa');
});
