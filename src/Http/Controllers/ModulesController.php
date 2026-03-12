<?php

namespace HolartWeb\HolartCMS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use HolartWeb\HolartCMS\Models\TAdminAction;
use HolartWeb\HolartCMS\Models\TModule;

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
            [
                'id' => 'pagebuilder',
                'name' => 'Конструктор страниц',
                'description' => 'Модуль для создания и редактирования страниц с помощью визуального конструктора блоков. Требует установленный модуль "Страницы и SEO"',
                'installed' => $this->isPageBuilderModuleInstalled(),
                'dependencies' => ['seo'],
                'can_install' => $this->isSeoModuleInstalled(),
            ],
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
            ]
        ];

        return response()->json([
            'modules' => $modules,
            'show_modules_page' => config('holart-cms.show_modules', false)
        ]);
    }

    /**
     * Get list of available modules with their status (for modules management page)
     */
    public function index()
    {
        // Check if modules page is enabled
        if (!config('holart-cms.show_modules', false)) {
            abort(404);
        }

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
                'id' => 'seo',
                'name' => 'Страницы и SEO',
                'description' => 'Модуль для создания статических страниц и управления SEO-оптимизацией: мета-теги, sitemap, ЧПУ.',
                'installed' => $this->isSeoModuleInstalled(),
                'install_command' => 'holartcms:seo-install',
                'uninstall_command' => 'holartcms:seo-uninstall',
            ],
            [
                'id' => 'pagebuilder',
                'name' => 'Конструктор страниц',
                'description' => 'Модуль для создания и редактирования страниц с помощью визуального конструктора блоков. Требует установленный модуль "Страницы и SEO"',
                'installed' => $this->isPageBuilderModuleInstalled(),
                'install_command' => 'holartcms:pagebuilder-install',
                'uninstall_command' => 'holartcms:pagebuilder-uninstall',
                'dependencies' => ['seo'],
                'can_install' => $this->isSeoModuleInstalled(),
            ],
            [
                'id' => 'telegram',
                'name' => 'Telegram',
                'description' => 'Интеграция с Telegram для отправки уведомлений через бота',
                'installed' => $this->isTelegramIntegrationInstalled(),
                'install_command' => 'holartcms:telegram-install',
                'uninstall_command' => 'holartcms:telegram-uninstall',
                'type' => 'integration',
            ],
            [
                'id' => 'yookassa',
                'name' => 'ЮКassa',
                'description' => 'Интеграция с платежной системой ЮКassa для приема платежей. Требует установленный модуль "Коммерция"',
                'installed' => $this->isYookassaIntegrationInstalled(),
                'install_command' => 'holartcms:yookassa-install',
                'uninstall_command' => 'holartcms:yookassa-uninstall',
                'type' => 'integration',
                'dependencies' => ['commerce'],
                'can_install' => $this->isCommerceModuleInstalled(),
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
                case 'seo':
                    $exitCode = Artisan::call('holartcms:seo-install');
                    break;
                case 'pagebuilder':
                    $exitCode = Artisan::call('holartcms:pagebuilder-install');
                    break;
                case 'telegram':
                    $exitCode = Artisan::call('holartcms:telegram-install');
                    break;
                case 'yookassa':
                    $exitCode = Artisan::call('holartcms:yookassa-install');
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
            $moduleNames = ['shop' => 'Каталог и товары', 'callback' => 'Обратная связь', 'commerce' => 'Коммерция', 'logging' => 'Журнал активности', 'infoblocks' => 'Информационные блоки', 'seo' => 'Страницы и SEO', 'pagebuilder' => 'Конструктор страниц'];
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
                case 'seo':
                    $exitCode = Artisan::call('holartcms:seo-install');
                    break;
                case 'pagebuilder':
                    $exitCode = Artisan::call('holartcms:pagebuilder-install');
                    break;
                case 'telegram':
                    $exitCode = Artisan::call('holartcms:telegram-install');
                    break;
                case 'yookassa':
                    $exitCode = Artisan::call('holartcms:yookassa-install');
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
                case 'seo':
                    Artisan::call('holartcms:seo-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'pagebuilder':
                    Artisan::call('holartcms:pagebuilder-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'telegram':
                    Artisan::call('holartcms:telegram-uninstall', [
                        '--preserve-db' => $preserveDatabase
                    ]);
                    break;
                case 'yookassa':
                    Artisan::call('holartcms:yookassa-uninstall', [
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
