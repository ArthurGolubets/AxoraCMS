<?php

namespace HolartWeb\HolartCMS\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagesInstallCommand extends Command
{
    protected $signature = 'holartcms:pages-install';
    protected $description = 'Install HolartCMS Pages Module';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════╗');
        $this->info('║ HolartCMS Pages Module Installer  ║');
        $this->info('╚════════════════════════════════════╝');
        $this->newLine();

        // Determine package path (works for both local development and composer installation)
        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        // Step 1: Copy Pages and Menus Models
        $this->info('Step 1: Copying pages and menus models...');
        $appModelsPath = app_path('Models');

        // Copy Pages models
        $models = ['TPage.php', 'TPageBlock.php', 'TPageBlockType.php'];
        foreach ($models as $model) {
            $source = $packagePath . '/src/Models/Pages/' . $model;
            $destination = $appModelsPath . '/' . $model;

            if (file_exists($source)) {
                $content = file_get_contents($source);
                // Update namespace from package to app
                $content = str_replace(
                    'namespace HolartWeb\HolartCMS\Models\Pages;',
                    'namespace App\Models;',
                    $content
                );
                file_put_contents($destination, $content);
                $this->info("✓ Copied {$model}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }

        // Copy Menus models
        $menuModels = ['TMenu.php', 'TMenuItem.php'];
        foreach ($menuModels as $model) {
            $source = $packagePath . '/src/Models/Menus/' . $model;
            $destination = $appModelsPath . '/' . $model;

            if (file_exists($source)) {
                $content = file_get_contents($source);
                // Update namespace from package to app
                $content = str_replace(
                    'namespace HolartWeb\HolartCMS\Models\Menus;',
                    'namespace App\Models;',
                    $content
                );
                file_put_contents($destination, $content);
                $this->info("✓ Copied {$model}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }
        $this->newLine();

        // Step 2: Copy Pages and Menus Controllers
        $this->info('Step 2: Copying pages and menus controllers...');
        $appControllersPath = app_path('Http/Controllers');

        // Copy Pages controllers
        $controllers = [
            'PagesController.php',
            'PageBlocksController.php',
            'PageBlockTypesController.php'
        ];

        foreach ($controllers as $controller) {
            $source = $packagePath . '/src/Http/Controllers/Pages/' . $controller;
            $destination = $appControllersPath . '/' . $controller;

            if (file_exists($source)) {
                $content = file_get_contents($source);
                // Update namespace from package to app
                $content = str_replace(
                    'namespace HolartWeb\HolartCMS\Http\Controllers\Pages;',
                    'namespace App\Http\Controllers;',
                    $content
                );
                $content = str_replace(
                    'use Illuminate\Routing\Controller;',
                    '',
                    $content
                );
                $content = str_replace(
                    'use HolartWeb\HolartCMS\Models\Pages\TPage;',
                    'use App\Models\TPage;',
                    $content
                );
                $content = str_replace(
                    'use HolartWeb\HolartCMS\Models\Pages\TPageBlock;',
                    'use App\Models\TPageBlock;',
                    $content
                );
                $content = str_replace(
                    'use HolartWeb\HolartCMS\Models\Pages\TPageBlockType;',
                    'use App\Models\TPageBlockType;',
                    $content
                );
                file_put_contents($destination, $content);
                $this->info("✓ Copied {$controller}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }

        // Copy Menus controllers
        $menuControllers = [
            'MenusController.php',
            'MenuItemsController.php'
        ];

        foreach ($menuControllers as $controller) {
            $source = $packagePath . '/src/Http/Controllers/Menus/' . $controller;
            $destination = $appControllersPath . '/' . $controller;

            if (file_exists($source)) {
                $content = file_get_contents($source);
                // Update namespace from package to app
                $content = str_replace(
                    'namespace HolartWeb\HolartCMS\Http\Controllers\Menus;',
                    'namespace App\Http\Controllers;',
                    $content
                );
                $content = str_replace(
                    'use Illuminate\Routing\Controller;',
                    '',
                    $content
                );
                $content = str_replace(
                    'use HolartWeb\HolartCMS\Models\Menus\TMenu;',
                    'use App\Models\TMenu;',
                    $content
                );
                $content = str_replace(
                    'use HolartWeb\HolartCMS\Models\Menus\TMenuItem;',
                    'use App\Models\TMenuItem;',
                    $content
                );
                file_put_contents($destination, $content);
                $this->info("✓ Copied {$controller}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }
        $this->newLine();

        // Step 3: Copy Blade Templates
        $this->info('Step 3: Copying blade templates...');
        $viewsSource = $packagePath . '/resources/views/components';
        $viewsDestination = resource_path('views/components');

        // Create components directory if it doesn't exist
        if (!file_exists($viewsDestination)) {
            mkdir($viewsDestination, 0755, true);
        }

        // Create blocks subdirectory
        $blocksDestination = $viewsDestination . '/blocks';
        if (!file_exists($blocksDestination)) {
            mkdir($blocksDestination, 0755, true);
        }

        // Copy all blade files
        $bladeFiles = [
            'blocks/hero-1.blade.php',
            'blocks/hero-2.blade.php',
            'blocks/text.blade.php',
            'blocks/rich-text.blade.php',
            'blocks/cards.blade.php',
            'blocks/products.blade.php',
            'blocks/catalogs.blade.php',
            'blocks/features.blade.php',
            'blocks/breadcrumbs.blade.php',
            'blocks/slider.blade.php',
            'blocks/container-50-50.blade.php',
            'blocks/container-33-33-33.blade.php',
            'blocks/container-25-75.blade.php',
            'page-renderer.blade.php',
        ];

        foreach ($bladeFiles as $file) {
            $source = $viewsSource . '/' . $file;
            $destination = $viewsDestination . '/' . $file;

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied {$file}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }

        // Copy header templates
        $headersSource = $packagePath . '/resources/views/layouts/headers';
        $headersDestination = resource_path('views/layouts/headers');

        if (!file_exists($headersDestination)) {
            mkdir($headersDestination, 0755, true);
        }

        $headerFiles = ['header1.blade.php', 'header2.blade.php', 'header3.blade.php'];
        foreach ($headerFiles as $file) {
            $source = $headersSource . '/' . $file;
            $destination = $headersDestination . '/' . $file;

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied {$file}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }

        // Create custom header template if not exists
        $customHeaderPath = $headersDestination . '/custom.blade.php';
        if (!file_exists($customHeaderPath)) {
            $customHeaderContent = <<<'BLADE'
{{-- Custom Header Template --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');
    $logoWidth = TPanelSettings::get('logo_width');

    // Get custom header settings
    $headerSettings = TPanelSettings::get('header_template_settings', []);
    $customSettings = is_array($headerSettings) && isset($headerSettings['custom']) ? $headerSettings['custom'] : [];

    $menuId = $customSettings['menu_id'] ?? null;
    $menu = $menuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $menuId)->where('is_active', true)->first() : null;

    $bgColor = $customSettings['bg_color'] ?? '#ffffff';
    $textColor = $customSettings['text_color'] ?? '#212529';
    $linkColor = $customSettings['link_color'] ?? '#495057';
    $linkHoverColor = $customSettings['link_hover_color'] ?? '#0d6efd';
@endphp

<header class="custom-header" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <a href="/" class="logo">
                @if($logoPath)
                    <img
                        src="{{ asset('storage/' . $logoPath) }}"
                        alt="{{ $siteName }}"
                        style="max-height: 50px; @if($logoWidth) width: {{ $logoWidth }}px; @endif"
                    />
                @else
                    <span class="text-xl font-bold">{{ $siteName }}</span>
                @endif
            </a>

            <!-- Menu -->
            @if($menu && $menu->rootItems->count() > 0)
            <nav>
                <ul class="flex gap-6">
                    @foreach($menu->rootItems as $item)
                        <li>
                            <a href="{{ $item->url }}" style="color: {{ $linkColor }};">
                                {{ $item->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            @endif
        </div>
    </div>
</header>

<style>
.custom-header a:hover {
    color: {{ $linkHoverColor }};
}
</style>
BLADE;
            file_put_contents($customHeaderPath, $customHeaderContent);
            $this->info("✓ Created custom.blade.php header template");
        }

        // Copy footer templates
        $footersSource = $packagePath . '/resources/views/layouts/footers';
        $footersDestination = resource_path('views/layouts/footers');

        if (!file_exists($footersDestination)) {
            mkdir($footersDestination, 0755, true);
        }

        $footerFiles = ['footer1.blade.php', 'footer2.blade.php', 'footer3.blade.php'];
        foreach ($footerFiles as $file) {
            $source = $footersSource . '/' . $file;
            $destination = $footersDestination . '/' . $file;

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied {$file}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }

        // Create custom footer template if not exists
        $customFooterPath = $footersDestination . '/custom.blade.php';
        if (!file_exists($customFooterPath)) {
            $customFooterContent = <<<'BLADE'
{{-- Custom Footer Template --}}
@php
    use HolartWeb\HolartCMS\Models\TPanelSettings;
    use HolartWeb\HolartCMS\Models\Menus\TMenu;

    $logoPath = TPanelSettings::get('logo_path');
    $siteName = TPanelSettings::get('site_name', 'HolartCMS');

    // Get custom footer settings
    $footerSettings = TPanelSettings::get('footer_template_settings', []);
    $customSettings = is_array($footerSettings) && isset($footerSettings['custom']) ? $footerSettings['custom'] : [];

    $menuId = $customSettings['menu_id'] ?? null;
    $menu = $menuId ? TMenu::with(['rootItems' => function ($query) {
        $query->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort');
            }]);
    }])->where('id', $menuId)->where('is_active', true)->first() : null;

    $bgColor = $customSettings['bg_color'] ?? '#ffffff';
    $textColor = $customSettings['text_color'] ?? '#212529';
    $linkColor = $customSettings['link_color'] ?? '#495057';
    $linkHoverColor = $customSettings['link_hover_color'] ?? '#0d6efd';
@endphp

<footer class="custom-footer mt-auto" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    <div class="container mx-auto px-4 py-8">
        <!-- Footer Menu -->
        @if($menu && $menu->rootItems->count() > 0)
        <nav class="mb-6">
            <ul class="flex flex-wrap justify-center gap-6">
                @foreach($menu->rootItems as $item)
                    <li>
                        <a href="{{ $item->url }}" style="color: {{ $linkColor }};">
                            {{ $item->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
        @endif

        <!-- Copyright -->
        <div class="text-center">
            <p class="text-sm">
                © {{ date('Y') }} {{ $siteName }}. Все права защищены.
            </p>
        </div>
    </div>
</footer>

<style>
.custom-footer a:hover {
    color: {{ $linkHoverColor }};
}
</style>
BLADE;
            file_put_contents($customFooterPath, $customFooterContent);
            $this->info("✓ Created custom.blade.php footer template");
        }

        // Copy main layout files
        $layoutsSource = $packagePath . '/resources/views/layouts';
        $layoutsDestination = resource_path('views/layouts');

        if (!file_exists($layoutsDestination)) {
            mkdir($layoutsDestination, 0755, true);
        }

        $layoutFiles = ['app.blade.php', 'simple.blade.php'];
        foreach ($layoutFiles as $file) {
            $source = $layoutsSource . '/' . $file;
            $destination = $layoutsDestination . '/' . $file;

            if (file_exists($source)) {
                copy($source, $destination);
                $this->info("✓ Copied {$file}");
            } else {
                $this->warn("⚠ Source file not found: {$source}");
            }
        }
        $this->newLine();

        // Step 4: Copy and Run Migrations
        $this->info('Step 4: Copying and running database migrations...');

        // Check if tables already exist
        $tablesExist = Schema::hasTable('t_pages') &&
                       Schema::hasTable('t_page_blocks') &&
                       Schema::hasTable('t_page_block_types');

        $menusTablesExist = Schema::hasTable('t_menus') && Schema::hasTable('t_menu_items');

        if ($tablesExist) {
            $this->warn('⚠ Pages tables already exist. Running update migrations only...');

            // Copy and run update migration
            $updateMigrationFile = '2026_03_02_000053_update_pages_add_container_support.php';
            $updateSource = $packagePath . '/database/migrations/pages/2026_03_02_update_pages_add_container_support.php';
            $updateDestination = database_path('migrations/' . $updateMigrationFile);

            if (file_exists($updateSource)) {
                copy($updateSource, $updateDestination);
                $this->info("✓ Copied update migration {$updateMigrationFile}");

                try {
                    Artisan::call('migrate', [
                        '--path' => 'database/migrations/' . $updateMigrationFile,
                        '--force' => true
                    ]);
                    $this->info('✓ Update migration completed successfully');
                } catch (\Exception $e) {
                    $this->warn('⚠ Update migration warning: ' . $e->getMessage());
                }
            }
        } else {
            // Copy and run pages migrations
            $migrationFiles = [
                '2026_03_02_000050_create_t_pages_table.php',
                '2026_03_02_000051_create_t_page_block_types_table.php',
                '2026_03_02_000052_create_t_page_blocks_table.php',
            ];

            $migrationsSource = $packagePath . '/database/migrations/pages/';

            foreach ($migrationFiles as $file) {
                $source = $migrationsSource . str_replace('2026_03_02_000050_', '2026_03_02_',
                    str_replace('2026_03_02_000051_', '2026_03_02_',
                    str_replace('2026_03_02_000052_', '2026_03_02_', $file)));
                $destination = database_path('migrations/' . $file);

                if (file_exists($source)) {
                    copy($source, $destination);
                    $this->info("✓ Copied migration {$file}");
                } else {
                    $this->warn("⚠ Source migration not found: {$source}");
                }
            }

            try {
                // Run only pages module migrations
                foreach ($migrationFiles as $file) {
                    $migrationPath = database_path('migrations/' . $file);
                    if (file_exists($migrationPath)) {
                        Artisan::call('migrate', [
                            '--path' => 'database/migrations/' . $file,
                            '--force' => true
                        ]);
                    }
                }
                $this->info('✓ Pages migrations completed successfully');
            } catch (\Exception $e) {
                $this->error('❌ Pages migration failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        // Copy and run menus migrations
        if (!$menusTablesExist) {
            $this->info('Installing menus tables...');
            $menuMigrationFiles = [
                '2026_03_03_000060_create_t_menus_table.php',
                '2026_03_03_000061_create_t_menu_items_table.php',
            ];

            $menuMigrationsSource = $packagePath . '/database/migrations/menus/';

            foreach ($menuMigrationFiles as $file) {
                $source = $menuMigrationsSource . str_replace('2026_03_03_000060_', '2026_03_03_',
                    str_replace('2026_03_03_000061_', '2026_03_03_', $file));
                $destination = database_path('migrations/' . $file);

                if (file_exists($source)) {
                    copy($source, $destination);
                    $this->info("✓ Copied migration {$file}");
                } else {
                    $this->warn("⚠ Source migration not found: {$source}");
                }
            }

            try {
                // Run menus migrations
                foreach ($menuMigrationFiles as $file) {
                    $migrationPath = database_path('migrations/' . $file);
                    if (file_exists($migrationPath)) {
                        Artisan::call('migrate', [
                            '--path' => 'database/migrations/' . $file,
                            '--force' => true
                        ]);
                    }
                }
                $this->info('✓ Menus migrations completed successfully');
            } catch (\Exception $e) {
                $this->error('❌ Menus migration failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info('✓ Menus tables already exist, skipping menus migrations');
        }
        $this->newLine();

        // Step 5: Seed Default Block Types
        $this->info('Step 5: Creating default block types...');
        $this->seedDefaultBlockTypes();
        $this->info('✓ Default block types created');
        $this->newLine();

        // Step 6: Build Frontend Assets
        $this->info('Step 6: Building frontend assets...');

        if (file_exists($packagePath . '/package.json')) {
            $this->info('Building assets...');
            exec("cd {$packagePath} && npm run build 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                $this->error('❌ Asset build failed');
                return self::FAILURE;
            }
            $this->info('✓ Assets built successfully');
        } else {
            $this->warn('⚠ package.json not found, skipping asset build');
        }
        $this->newLine();

        // Step 7: Publish Assets
        $this->info('Step 7: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'holart-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 8: Clear Cache
        $this->info('Step 8: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Success Message
        $this->info('╔═══════════════════════════════════════╗');
        $this->info('║ Pages Module Installed Successfully! ║');
        $this->info('╚═══════════════════════════════════════╝');
        $this->newLine();
        $this->info('You can now create pages in your admin panel.');
        $this->info('Navigate to: ' . url('/admin/pages'));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Seed default block types
     */
    protected function seedDefaultBlockTypes(): void
    {
        $blockTypes = [
            [
                'code' => 'container_50_50',
                'name' => 'Контейнер 50/50',
                'description' => 'Контейнер с двумя колонками по 50%',
                'icon' => 'heroicons:view-columns',
                'category' => 'layout',
                'is_system' => true,
                'is_container' => true,
                'fields_schema' => [
                    ['name' => 'gap', 'type' => 'select', 'label' => 'Отступ между колонками', 'options' => [
                        ['value' => 'small', 'label' => 'Маленький'],
                        ['value' => 'medium', 'label' => 'Средний'],
                        ['value' => 'large', 'label' => 'Большой'],
                    ]],
                ],
            ],
            [
                'code' => 'container_33_33_33',
                'name' => 'Контейнер 33/33/33',
                'description' => 'Контейнер с тремя равными колонками',
                'icon' => 'heroicons:view-columns',
                'category' => 'layout',
                'is_system' => true,
                'is_container' => true,
                'fields_schema' => [
                    ['name' => 'gap', 'type' => 'select', 'label' => 'Отступ между колонками', 'options' => [
                        ['value' => 'small', 'label' => 'Маленький'],
                        ['value' => 'medium', 'label' => 'Средний'],
                        ['value' => 'large', 'label' => 'Большой'],
                    ]],
                ],
            ],
            [
                'code' => 'container_25_75',
                'name' => 'Контейнер 25/75',
                'description' => 'Контейнер с колонками 25% и 75%',
                'icon' => 'heroicons:view-columns',
                'category' => 'layout',
                'is_system' => true,
                'is_container' => true,
                'fields_schema' => [
                    ['name' => 'gap', 'type' => 'select', 'label' => 'Отступ между колонками', 'options' => [
                        ['value' => 'small', 'label' => 'Маленький'],
                        ['value' => 'medium', 'label' => 'Средний'],
                        ['value' => 'large', 'label' => 'Большой'],
                    ]],
                ],
            ],
        ];

        foreach ($blockTypes as $blockType) {
            DB::table('t_page_block_types')->updateOrInsert(
                ['code' => $blockType['code']],
                array_merge($blockType, [
                    'fields_schema' => json_encode($blockType['fields_schema']),
                    'is_container' => $blockType['is_container'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
