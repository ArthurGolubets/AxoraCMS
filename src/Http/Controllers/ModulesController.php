<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TModule;

class ModulesController extends Controller
{
    // Module versions - должны совпадать с версиями в Install командах
    const MODULES_VERSIONS = [
        'shop' => '1.0.0',
        'callback' => '1.0.0',
        'commerce' => '1.0.0',
        'logging' => '1.0.0',
        'infoblocks' => '1.0.0',
        'seo' => '1.0.0',
        'pagebuilder' => '1.0.0',
        'telegram' => '1.0.0',
        'yookassa' => '1.0.0',
        'commerceml' => '1.0.0',
    ];

    /**
     * Get modules status (always accessible for sidebar menu)
     */
    public function status()
    {
        $modules = [
            [
                'id' => 'shop',
                'name' => 'Каталог и товары',
                'description' => 'Модуль, который добавит на сайт систему каталогов, товаров, и их логику',
                'installed' => $this->isShopModuleInstalled(),
            ],
            [
                'id' => 'callback',
                'name' => 'Обратная связь',
                'description' => 'Модуль для управления email подписками, комментариями и обращениями пользователей',
                'installed' => $this->isCallbackModuleInstalled(),
            ],
            [
                'id' => 'commerce',
                'name' => 'Коммерция',
                'description' => 'Модуль для управления заказами, промокодами и платежными транзакциями. Требует установленный модуль "Каталог и товары"',
                'installed' => $this->isCommerceModuleInstalled(),
                'dependencies' => ['shop'],
                'can_install' => $this->isShopModuleInstalled(),
            ],
            [
                'id' => 'logging',
                'name' => 'Журнал активности',
                'description' => 'Модуль для отслеживания всех действий администраторов: создание, редактирование, удаление товаров, категорий, заказов, установка модулей и изменение настроек',
                'installed' => $this->isLoggingModuleInstalled(),
            ],
            [
                'id' => 'infoblocks',
                'name' => 'Информационные блоки',
                'description' => 'Модуль для создания пользовательских сущностей с динамическими полями.',
                'installed' => $this->isInfoBlocksModuleInstalled(),
            ],
            [
                'id' => 'seo',
                'name' => 'Страницы и SEO',
                'description' => 'Модуль для создания статических страниц и управления SEO-оптимизацией: мета-теги, sitemap, ЧПУ.',
                'installed' => $this->isSeoModuleInstalled(),
            ],
            /*[
                'id' => 'pagebuilder',
                'name' => 'Конструктор страниц',
                'description' => 'Модуль для создания и редактирования страниц с помощью визуального конструктора блоков. Требует установленный модуль "Страницы и SEO"',
                'installed' => $this->isPageBuilderModuleInstalled(),
                'dependencies' => ['seo'],
                'can_install' => $this->isSeoModuleInstalled(),
            ],*/
            [
                'id' => 'telegram',
                'name' => 'Telegram',
                'description' => 'Интеграция с Telegram для отправки уведомлений через бота',
                'installed' => $this->isTelegramIntegrationInstalled(),
                'type' => 'integration',
            ],
            [
                'id' => 'yookassa',
                'name' => 'ЮКassa',
                'description' => 'Интеграция с платежной системой ЮКassa для приема платежей. Требует установленный модуль "Коммерция"',
                'installed' => $this->isYookassaIntegrationInstalled(),
                'type' => 'integration',
                'dependencies' => ['commerce'],
                'can_install' => $this->isCommerceModuleInstalled(),
            ],
            [
                'id' => 'commerceml',
                'name' => 'CommerceML',
                'description' => 'Интеграция с 1С через протокол CommerceML для автоматической синхронизации товаров и категорий. Требует установленный модуль "Каталог и товары"',
                'installed' => $this->isCommerceMLIntegrationInstalled(),
                'type' => 'integration',
                'dependencies' => ['shop'],
                'can_install' => $this->isShopModuleInstalled(),
            ]
        ];

        return response()->json([
            'modules' => $modules,
            'show_modules_page' => config('axora-cms.show_modules', false)
        ]);
    }

    /**
     * Get list of available modules with their status (for modules management page)
     */
    public function index()
    {
        // Check if modules page is enabled
        if (!config('axora-cms.show_modules', false)) {
            abort(404);
        }

        $modules = [
            [
                'id' => 'shop',
                'name' => 'Каталог и товары',
                'description' => 'Модуль, который добавит на сайт систему каталогов, товаров, и их логику',
                'installed' => $this->isShopModuleInstalled(),
                'install_command' => 'axoracms:shop-install',
                'uninstall_command' => 'axoracms:shop-uninstall',
            ],
            [
                'id' => 'callback',
                'name' => 'Обратная связь',
                'description' => 'Модуль для управления email подписками, комментариями и обращениями пользователей',
                'installed' => $this->isCallbackModuleInstalled(),
                'install_command' => 'axoracms:callback-user-install',
                'uninstall_command' => 'axoracms:callback-user-uninstall',
            ],
            [
                'id' => 'commerce',
                'name' => 'Коммерция',
                'description' => 'Модуль для управления заказами, промокодами и платежными транзакциями. Требует установленный модуль "Каталог и товары"',
                'installed' => $this->isCommerceModuleInstalled(),
                'install_command' => 'axoracms:commerce-install',
                'uninstall_command' => 'axoracms:commerce-uninstall',
                'dependencies' => ['shop'],
                'can_install' => $this->isShopModuleInstalled(),
            ],
            [
                'id' => 'logging',
                'name' => 'Журнал активности',
                'description' => 'Модуль для отслеживания всех действий администраторов: создание, редактирование, удаление товаров, категорий, заказов, установка модулей и изменение настроек',
                'installed' => $this->isLoggingModuleInstalled(),
                'install_command' => 'axoracms:logging-install',
                'uninstall_command' => 'axoracms:logging-uninstall',
            ],
            [
                'id' => 'infoblocks',
                'name' => 'Информационные блоки',
                'description' => 'Модуль для создания пользовательских сущностей с динамическими полями.',
                'installed' => $this->isInfoBlocksModuleInstalled(),
                'install_command' => 'axoracms:infoblocks-install',
                'uninstall_command' => 'axoracms:infoblocks-uninstall',
            ],
            [
                'id' => 'seo',
                'name' => 'Страницы и SEO',
                'description' => 'Модуль для создания статических страниц и управления SEO-оптимизацией: мета-теги, sitemap, ЧПУ.',
                'installed' => $this->isSeoModuleInstalled(),
                'install_command' => 'axoracms:seo-install',
                'uninstall_command' => 'axoracms:seo-uninstall',
            ],
            /*[
                'id' => 'pagebuilder',
                'name' => 'Конструктор страниц',
                'description' => 'Модуль для создания и редактирования страниц с помощью визуального конструктора блоков. Требует установленный модуль "Страницы и SEO"',
                'installed' => $this->isPageBuilderModuleInstalled(),
                'install_command' => 'axoracms:pagebuilder-install',
                'uninstall_command' => 'axoracms:pagebuilder-uninstall',
                'dependencies' => ['seo'],
                'can_install' => $this->isSeoModuleInstalled(),
            ],*/
            [
                'id' => 'telegram',
                'name' => 'Telegram',
                'description' => 'Интеграция с Telegram для отправки уведомлений через бота',
                'installed' => $this->isTelegramIntegrationInstalled(),
                'install_command' => 'axoracms:telegram-install',
                'uninstall_command' => 'axoracms:telegram-uninstall',
                'type' => 'integration',
            ],
            [
                'id' => 'yookassa',
                'name' => 'ЮКassa',
                'description' => 'Интеграция с платежной системой ЮКassa для приема платежей. Требует установленный модуль "Коммерция"',
                'installed' => $this->isYookassaIntegrationInstalled(),
                'install_command' => 'axoracms:yookassa-install',
                'uninstall_command' => 'axoracms:yookassa-uninstall',
                'type' => 'integration',
                'dependencies' => ['commerce'],
                'can_install' => $this->isCommerceModuleInstalled(),
            ],
            [
                'id' => 'commerceml',
                'name' => 'CommerceML',
                'description' => 'Интеграция с 1С через протокол CommerceML для автоматической синхронизации товаров и категорий. Требует установленный модуль "Каталог и товары"',
                'installed' => $this->isCommerceMLIntegrationInstalled(),
                'install_command' => 'axoracms:commerceml-install',
                'uninstall_command' => 'axoracms:commerceml-uninstall',
                'type' => 'integration',
                'dependencies' => ['shop'],
                'can_install' => $this->isShopModuleInstalled(),
            ]
        ];

        // Add version information to each module
        $modules = array_map(function($module) {
            $moduleId = $module['id'];
            $module['current_version'] = self::MODULES_VERSIONS[$moduleId] ?? null;
            $module['installed_version'] = TModule::getInstalledVersion($moduleId);
            $module['needs_update'] = $module['installed'] &&
                                      $module['installed_version'] &&
                                      $module['current_version'] &&
                                      version_compare($module['installed_version'], $module['current_version'], '<');
            return $module;
        }, $modules);

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
            Artisan::call('axoracms:update');

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
                    $exitCode = Artisan::call('axoracms:shop-install');
                    break;
                case 'callback':
                    $exitCode = Artisan::call('axoracms:callback-user-install');
                    break;
                case 'commerce':
                    $exitCode = Artisan::call('axoracms:commerce-install');
                    break;
                case 'logging':
                    $exitCode = Artisan::call('axoracms:logging-install');
                    break;
                case 'infoblocks':
                    $exitCode = Artisan::call('axoracms:infoblocks-install');
                    break;
                case 'seo':
                    $exitCode = Artisan::call('axoracms:seo-install');
                    break;
                case 'pagebuilder':
                    $exitCode = Artisan::call('axoracms:pagebuilder-install');
                    break;
                case 'telegram':
                    $exitCode = Artisan::call('axoracms:telegram-install');
                    break;
                case 'yookassa':
                    $exitCode = Artisan::call('axoracms:yookassa-install');
                    break;
                case 'commerceml':
                    $exitCode = Artisan::call('axoracms:commerceml-install');
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
            $moduleNames = ['shop' => 'Каталог и товары', 'callback' => 'Обратная связь', 'commerce' => 'Коммерция', 'logging' => 'Журнал активности', 'infoblocks' => 'Информационные блоки', 'seo' => 'Страницы и SEO',
                'pagebuilder' => 'Конструктор страниц'
            ];
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
     * Update a module
     */
    public function updateModule(Request $request, $moduleId)
    {
        $request->validate([
            'module_id' => 'required|string'
        ]);

        try {
            $exitCode = 0;

            // Update is essentially a reinstall that preserves data
            switch ($moduleId) {
                case 'shop':
                    $exitCode = Artisan::call('axoracms:shop-install');
                    break;
                case 'callback':
                    $exitCode = Artisan::call('axoracms:callback-user-install');
                    break;
                case 'commerce':
                    $exitCode = Artisan::call('axoracms:commerce-install');
                    break;
                case 'logging':
                    $exitCode = Artisan::call('axoracms:logging-install');
                    break;
                case 'infoblocks':
                    $exitCode = Artisan::call('axoracms:infoblocks-install');
                    break;
                case 'seo':
                    $exitCode = Artisan::call('axoracms:seo-install');
                    break;
                case 'pagebuilder':
                    $exitCode = Artisan::call('axoracms:pagebuilder-install');
                    break;
                case 'telegram':
                    $exitCode = Artisan::call('axoracms:telegram-install');
                    break;
                case 'yookassa':
                    $exitCode = Artisan::call('axoracms:yookassa-install');
                    break;
                case 'commerceml':
                    $exitCode = Artisan::call('axoracms:commerceml-install');
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
                    'message' => 'Ошибка при обновлении модуля. Проверьте вывод команды.'
                ], 400);
            }

            // Log activity
            $moduleNames = [
                'shop' => 'Каталог и товары',
                'callback' => 'Обратная связь',
                'commerce' => 'Коммерция',
                'logging' => 'Журнал активности',
                'infoblocks' => 'Информационные блоки',
                'seo' => 'Страницы и SEO',
                'pagebuilder' => 'Конструктор страниц',
                'telegram' => 'Telegram',
                'yookassa' => 'ЮКassa'
            ];
            $moduleName = $moduleNames[$moduleId] ?? $moduleId;
            TAdminAction::log('updated', 'module', null, 'Обновлен модуль: ' . $moduleName);

            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'Модуль обновлен успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении модуля: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uninstall a module
     */
    public function uninstall(Request $request, $moduleId)
    {
        $request->validate([
            'preserve_database' => 'boolean',
            'remove_components' => 'boolean'
        ]);

        $preserveDatabase = $request->input('preserve_database', false);
        $removeComponents = $request->input('remove_components', false);

        try {
            switch ($moduleId) {
                case 'shop':
                    Artisan::call('axoracms:shop-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'callback':
                    Artisan::call('axoracms:callback-user-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'commerce':
                    Artisan::call('axoracms:commerce-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'logging':
                    Artisan::call('axoracms:logging-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'infoblocks':
                    Artisan::call('axoracms:infoblocks-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'seo':
                    Artisan::call('axoracms:seo-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'pagebuilder':
                    Artisan::call('axoracms:pagebuilder-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'telegram':
                    Artisan::call('axoracms:telegram-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'yookassa':
                    Artisan::call('axoracms:yookassa-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'commerceml':
                    Artisan::call('axoracms:commerceml-uninstall', [
                        '--preserve-db' => $preserveDatabase
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
            $moduleNames = ['shop' => 'Каталог и товары', 'callback' => 'Обратная связь', 'commerce' => 'Коммерция', 'logging' => 'Журнал активности', 'infoblocks' => 'Информационные блоки', 'seo' => 'Страницы и SEO', 'pagebuilder' => 'Конструктор страниц'];
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
        return TModule::isInstalled('shop');
    }

    /**
     * Check if callback module is installed
     */
    private function isCallbackModuleInstalled()
    {
        return TModule::isInstalled('callback');
    }

    /**
     * Check if commerce module is installed
     */
    private function isCommerceModuleInstalled()
    {
        return TModule::isInstalled('commerce');
    }

    /**
     * Check if logging module is installed
     */
    private function isLoggingModuleInstalled()
    {
        return TModule::isInstalled('logging');
    }

    /**
     * Check if infoblocks module is installed
     */
    private function isInfoBlocksModuleInstalled()
    {
        return TModule::isInstalled('infoblocks');
    }

    /**
     * Check if SEO module is installed
     */
    private function isSeoModuleInstalled()
    {
        return TModule::isInstalled('seo');
    }

    /**
     * Check if Page Builder module is installed
     */
    private function isPageBuilderModuleInstalled()
    {
        return TModule::isInstalled('pagebuilder');
    }

    /**
     * Check if Telegram integration is installed
     */
    private function isTelegramIntegrationInstalled()
    {
        return TModule::isInstalled('telegram');
    }

    /**
     * Check if Yookassa integration is installed
     */
    private function isYookassaIntegrationInstalled()
    {
        return TModule::isInstalled('yookassa');
    }

    /**
     * Check if CommerceML integration is installed
     */
    private function isCommerceMLIntegrationInstalled()
    {
        return TModule::isInstalled('commerceml');
    }

    /**
     * Check database migrations for all installed modules
     */
    public function checkDatabase()
    {
        try {
            $results = [];
            $hasIssues = false;

            // Define migrations for each module
            $moduleMigrations = [
                'shop' => [
                    '2024_01_01_000010_create_t_catalogs_table',
                    '2024_01_01_000011_create_t_products_table',
                    '2024_01_01_000012_create_t_product_variants_table',
                    '2026_03_29_000090_create_t_catalog_properties_table',
                    '2026_03_29_000091_create_t_product_property_values_table',
                    '2026_08_02_135901_create_t_product_variant_property_values_table',
                ],
                'callback' => [
                    '2024_01_01_000020_create_t_users_emails_table',
                    '2024_01_01_000021_create_t_comments_table',
                    '2024_01_01_000022_create_t_user_requests_table',
                ],
                'commerce' => [
                    '2024_01_01_000030_create_t_orders_table',
                    '2024_01_01_000031_create_t_order_items_table',
                    '2024_01_01_000032_create_t_promocodes_table',
                    '2024_01_01_000033_create_t_payment_transactions_table',
                    '2024_01_01_000034_create_t_orders_data_table',
                    '2026_08_08_000001_add_variant_id_to_t_order_items_table',
                ],
                'logging' => [
                    '2026_02_27_125658_create_t_admin_actions_table',
                ],
                'infoblocks' => [
                    '2024_01_01_000040_create_t_info_blocks_table',
                    '2024_01_01_000041_create_t_info_block_fields_table',
                    '2024_01_01_000042_create_t_info_block_elements_table',
                ],
                'seo' => [
                    '2024_03_09_000001_create_t_pages_table',
                ],
                'pagebuilder' => [
                    '2024_01_01_000070_create_t_page_blocks_table',
                ],
                'telegram' => [
                    '2024_01_01_000080_create_t_telegram_settings_table',
                ],
                'yookassa' => [
                    '2024_01_01_000090_create_t_yookassa_settings_table',
                ],
                'commerceml' => [
                    '2024_01_01_000100_create_t_commerceml_settings_table',
                ],
            ];

            // Get all installed modules
            $installedModules = TModule::all();

            foreach ($installedModules as $module) {
                $moduleId = $module->module_name;

                if (!isset($moduleMigrations[$moduleId])) {
                    continue;
                }

                $migrations = $moduleMigrations[$moduleId];
                $missing = [];
                $installed = [];

                foreach ($migrations as $migration) {
                    $exists = \DB::table('migrations')
                        ->where('migration', $migration)
                        ->exists();

                    if ($exists) {
                        $installed[] = $migration;
                    } else {
                        $missing[] = $migration;
                        $hasIssues = true;
                    }
                }

                $results[] = [
                    'module' => $moduleId,
                    'total' => count($migrations),
                    'installed' => count($installed),
                    'missing' => $missing,
                    'status' => empty($missing) ? 'ok' : 'error'
                ];
            }

            return response()->json([
                'success' => true,
                'has_issues' => $hasIssues,
                'results' => $results,
                'message' => $hasIssues
                    ? 'Обнаружены проблемы с базой данных'
                    : 'Все миграции установлены корректно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке базы данных: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Install missing migrations for modules
     */
    public function installMissingMigrations()
    {
        try {
            $output = [];

            // Define migrations for each module
            $moduleMigrations = [
                'shop' => [
                    '2024_01_01_000010_create_t_catalogs_table',
                    '2024_01_01_000011_create_t_products_table',
                    '2024_01_01_000012_create_t_product_variants_table',
                    '2026_03_29_000090_create_t_catalog_properties_table',
                    '2026_03_29_000091_create_t_product_property_values_table',
                    '2026_08_02_135901_create_t_product_variant_property_values_table',
                ],
                'callback' => [
                    '2024_01_01_000020_create_t_users_emails_table',
                    '2024_01_01_000021_create_t_comments_table',
                    '2024_01_01_000022_create_t_user_requests_table',
                ],
                'commerce' => [
                    '2024_01_01_000030_create_t_orders_table',
                    '2024_01_01_000031_create_t_order_items_table',
                    '2024_01_01_000032_create_t_promocodes_table',
                    '2024_01_01_000033_create_t_payment_transactions_table',
                    '2024_01_01_000034_create_t_orders_data_table',
                    '2026_08_08_000001_add_variant_id_to_t_order_items_table',
                ],
                'logging' => [
                    '2026_02_27_125658_create_t_admin_actions_table',
                ],
                'infoblocks' => [
                    '2024_01_01_000040_create_t_info_blocks_table',
                    '2024_01_01_000041_create_t_info_block_fields_table',
                    '2024_01_01_000042_create_t_info_block_elements_table',
                ],
                'seo' => [
                    '2024_03_09_000001_create_t_pages_table',
                ],
                'pagebuilder' => [
                    '2024_01_01_000070_create_t_page_blocks_table',
                ],
                'telegram' => [
                    '2024_01_01_000080_create_t_telegram_settings_table',
                ],
                'yookassa' => [
                    '2024_01_01_000090_create_t_yookassa_settings_table',
                ],
                'commerceml' => [
                    '2024_01_01_000100_create_t_commerceml_settings_table',
                ],
            ];

            // Map module IDs to their migration directories
            $modulePaths = [
                'shop' => 'shop',
                'callback' => 'callback',
                'commerce' => 'commerce',
                'logging' => 'logging',
                'infoblocks' => 'infoblocks',
                'seo' => 'seo',
                'pagebuilder' => 'pagebuilder',
                'telegram' => 'telegram',
                'yookassa' => 'yookassa',
                'commerceml' => 'commerceml',
            ];

            // Determine package path (check vendor first, then packages, then plugins for development)
            $packagePath = base_path('vendor/holartweb/axora-cms');
            if (!file_exists($packagePath)) {
                $packagePath = base_path('packages/holartweb/axora-cms');
                if (!file_exists($packagePath)) {
                    $packagePath = base_path('plugins/axora');
                }
            }

            // Get all installed modules
            $installedModules = TModule::all();
            $installedCount = 0;

            foreach ($installedModules as $module) {
                $moduleId = $module->module_name;

                if (!isset($moduleMigrations[$moduleId]) || !isset($modulePaths[$moduleId])) {
                    continue;
                }

                $migrations = $moduleMigrations[$moduleId];
                $modulePath = $modulePaths[$moduleId];

                foreach ($migrations as $migration) {
                    // Check if migration already exists
                    $exists = \DB::table('migrations')
                        ->where('migration', $migration)
                        ->exists();

                    if (!$exists) {
                        // Copy migration file to database/migrations
                        $sourceFile = $migration . '.php';
                        $source = $packagePath . '/database/migrations/' . $modulePath . '/' . $sourceFile;
                        $destination = database_path('migrations/' . $sourceFile);

                        if (file_exists($source)) {
                            // Remove old file if exists
                            if (file_exists($destination)) {
                                unlink($destination);
                            }

                            copy($source, $destination);
                            $output[] = "Скопирован файл миграции: {$sourceFile}";

                            // Run the migration
                            try {
                                Artisan::call('migrate', [
                                    '--path' => 'database/migrations/' . $sourceFile,
                                    '--force' => true
                                ]);

                                $output[] = "✓ Установлена миграция: {$migration}";
                                $installedCount++;
                            } catch (\Exception $e) {
                                $output[] = "✗ Ошибка при установке {$migration}: " . $e->getMessage();
                            }
                        } else {
                            $output[] = "⚠ Файл миграции не найден: {$source}";
                        }
                    }
                }
            }

            // Log activity
            TAdminAction::log('installed', 'migrations', null, "Установлено пропущенных миграций: {$installedCount}");

            return response()->json([
                'success' => true,
                'installed_count' => $installedCount,
                'output' => implode("\n", $output),
                'message' => $installedCount > 0
                    ? "Установлено миграций: {$installedCount}"
                    : 'Все миграции уже установлены'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при установке миграций: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get license key from settings
     */
    private function getLicenseKey()
    {
        if (class_exists('HolartWeb\AxoraCMS\Models\TPanelSettings')) {
            $settings = \HolartWeb\AxoraCMS\Models\TPanelSettings::first();
            return $settings->license_key ?? '';
        }
        return '';
    }
}
