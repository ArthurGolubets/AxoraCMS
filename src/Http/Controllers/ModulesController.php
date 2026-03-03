<?php

namespace HolartWeb\HolartCMS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use HolartWeb\HolartCMS\Models\TAdminAction;

class ModulesController extends Controller
{
    /**
     * Get list of available modules with their status
     */
    public function index()
    {
        $modules = [
            [
                'id' => 'shop',
                'name' => 'Каталог и товары',
                'description' => 'Модуль, который добавит на сайт систему каталогов, товаров, и их логику',
                'installed' => $this->isShopModuleInstalled(),
                'install_command' => 'holartcms:shop-install',
                'uninstall_command' => 'holartcms:shop-uninstall',
            ],
            [
                'id' => 'callback',
                'name' => 'Обратная связь',
                'description' => 'Модуль для управления email подписками, комментариями и обращениями пользователей',
                'installed' => $this->isCallbackModuleInstalled(),
                'install_command' => 'holartcms:callback-user-install',
                'uninstall_command' => 'holartcms:callback-user-uninstall',
            ],
            [
                'id' => 'commerce',
                'name' => 'Коммерция',
                'description' => 'Модуль для управления заказами, промокодами и платежными транзакциями. Требует установленный модуль "Каталог и товары"',
                'installed' => $this->isCommerceModuleInstalled(),
                'install_command' => 'holartcms:commerce-install',
                'uninstall_command' => 'holartcms:commerce-uninstall',
                'dependencies' => ['shop'],
                'can_install' => $this->isShopModuleInstalled(),
            ],
            [
                'id' => 'logging',
                'name' => 'Журнал активности',
                'description' => 'Модуль для отслеживания всех действий администраторов: создание, редактирование, удаление товаров, категорий, заказов, установка модулей и изменение настроек',
                'installed' => $this->isLoggingModuleInstalled(),
                'install_command' => 'holartcms:logging-install',
                'uninstall_command' => 'holartcms:logging-uninstall',
            ],
            [
                'id' => 'infoblocks',
                'name' => 'Информационные блоки',
                'description' => 'Модуль для создания пользовательских сущностей с динамическими полями.',
                'installed' => $this->isInfoBlocksModuleInstalled(),
                'install_command' => 'holartcms:infoblocks-install',
                'uninstall_command' => 'holartcms:infoblocks-uninstall',
            ],
            [
                'id' => 'pages',
                'name' => 'Страницы и СЕО',
                'description' => 'Модуль для создания статических и динамических страниц с конструктором блоков и SEO-оптимизацией.',
                'installed' => $this->isPagesModuleInstalled(),
                'install_command' => 'holartcms:pages-install',
                'uninstall_command' => 'holartcms:pages-uninstall',
            ]
        ];

        return response()->json([
            'modules' => $modules
        ]);
    }

    /**
     * Execute system update command
     */
    public function update(Request $request)
    {
        try {
            Artisan::call('holartcms:update');

            $output = Artisan::output();

            // Log activity
            TAdminAction::log('updated', 'system', null, 'Выполнено обновление системы');

            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'Обновление выполнено успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Install a module
     */
    public function install(Request $request, $moduleId)
    {
        $request->validate([
            'module_id' => 'required|string'
        ]);

        try {
            $exitCode = 0;

            switch ($moduleId) {
                case 'shop':
                    $exitCode = Artisan::call('holartcms:shop-install');
                    break;
                case 'callback':
                    $exitCode = Artisan::call('holartcms:callback-user-install');
                    break;
                case 'commerce':
                    $exitCode = Artisan::call('holartcms:commerce-install');
                    break;
                case 'logging':
                    $exitCode = Artisan::call('holartcms:logging-install');
                    break;
                case 'infoblocks':
                    $exitCode = Artisan::call('holartcms:infoblocks-install');
                    break;
                case 'pages':
                    $exitCode = Artisan::call('holartcms:pages-install');
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Неизвестный модуль'
                    ], 404);
            }

            $output = Artisan::output();

            // Check if command failed (exit code != 0)
            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'output' => $output,
                    'message' => 'Ошибка при установке модуля. Проверьте вывод команды.'
                ], 400);
            }

            // Log activity
            $moduleNames = ['shop' => 'Каталог и товары', 'callback' => 'Обратная связь', 'commerce' => 'Коммерция', 'logging' => 'Журнал активности', 'infoblocks' => 'Информационные блоки', 'pages' => 'Страницы и СЕО'];
            $moduleName = $moduleNames[$moduleId] ?? $moduleId;
            TAdminAction::log('installed', 'module', null, 'Установлен модуль: ' . $moduleName);

            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'Модуль установлен успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при установке модуля: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uninstall a module
     */
    public function uninstall(Request $request, $moduleId)
    {
        $request->validate([
            'preserve_database' => 'boolean'
        ]);

        $preserveDatabase = $request->input('preserve_database', false);

        try {
            switch ($moduleId) {
                case 'shop':
                    Artisan::call('holartcms:shop-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'callback':
                    Artisan::call('holartcms:callback-user-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'commerce':
                    Artisan::call('holartcms:commerce-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'logging':
                    Artisan::call('holartcms:logging-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'infoblocks':
                    Artisan::call('holartcms:infoblocks-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'pages':
                    Artisan::call('holartcms:pages-uninstall', [
                        '--preserve-db' => $preserveDatabase,
                        '--force' => true
                    ]);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Неизвестный модуль'
                    ], 404);
            }

            $output = Artisan::output();

            // Log activity
            $moduleNames = ['shop' => 'Каталог и товары', 'callback' => 'Обратная связь', 'commerce' => 'Коммерция', 'logging' => 'Журнал активности', 'infoblocks' => 'Информационные блоки', 'pages' => 'Страницы и СЕО'];
            $moduleName = $moduleNames[$moduleId] ?? $moduleId;
            TAdminAction::log('uninstalled', 'module', null, 'Удален модуль: ' . $moduleName);

            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'Модуль удален успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении модуля: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if shop module is installed
     */
    private function isShopModuleInstalled()
    {
        // Check if catalog and product controllers exist (they are copied during install)
        return file_exists(app_path('Http/Controllers/CatalogController.php')) &&
               file_exists(app_path('Http/Controllers/ProductController.php')) &&
               Schema::hasTable('t_catalogs') &&
               Schema::hasTable('t_products');
    }

    /**
     * Check if callback module is installed
     */
    private function isCallbackModuleInstalled()
    {
        return file_exists(app_path('Http/Controllers/UsersEmailsController.php')) &&
               file_exists(app_path('Http/Controllers/CommentsController.php')) &&
               file_exists(app_path('Http/Controllers/UserRequestsController.php')) &&
               Schema::hasTable('t_users_emails') &&
               Schema::hasTable('t_comments') &&
               Schema::hasTable('t_user_requests');
    }

    /**
     * Check if commerce module is installed
     */
    private function isCommerceModuleInstalled()
    {
        return file_exists(app_path('Http/Controllers/OrdersController.php')) &&
               file_exists(app_path('Http/Controllers/PromocodesController.php')) &&
               Schema::hasTable('t_orders') &&
               Schema::hasTable('t_promocodes');
    }

    /**
     * Check if logging module is installed
     */
    private function isLoggingModuleInstalled()
    {
        return file_exists(app_path('Http/Controllers/LogsController.php')) &&
               Schema::hasTable('t_admin_actions');
    }

    /**
     * Check if infoblocks module is installed
     */
    private function isInfoBlocksModuleInstalled()
    {
        return file_exists(app_path('Http/Controllers/InfoBlocksController.php')) &&
               file_exists(app_path('Models/TInfoBlock.php')) &&
               Schema::hasTable('t_info_blocks') &&
               Schema::hasTable('t_info_block_fields') &&
               Schema::hasTable('t_info_block_elements');
    }

    /**
     * Check if pages module is installed
     */
    private function isPagesModuleInstalled()
    {
        return file_exists(app_path('Http/Controllers/PagesController.php')) &&
               file_exists(app_path('Http/Controllers/MenusController.php')) &&
               file_exists(app_path('Models/TPage.php')) &&
               file_exists(app_path('Models/TMenu.php')) &&
               Schema::hasTable('t_pages') &&
               Schema::hasTable('t_page_blocks') &&
               Schema::hasTable('t_page_block_types') &&
               Schema::hasTable('t_menus') &&
               Schema::hasTable('t_menu_items');
    }

    /**
     * Get license key from settings
     */
    private function getLicenseKey()
    {
        if (class_exists('HolartWeb\HolartCMS\Models\TPanelSettings')) {
            $settings = \HolartWeb\HolartCMS\Models\TPanelSettings::first();
            return $settings->license_key ?? '';
        }
        return '';
    }
}
