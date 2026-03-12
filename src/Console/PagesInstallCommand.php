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

        // Determine package path
        $packagePath = base_path('vendor/holartweb/holart-cms');
        if (!file_exists($packagePath)) {
            $packagePath = base_path('packages/holartweb/holart-cms');
        }

        // Step 1: Run Migrations
        $this->info('Step 1: Running database migrations...');

        $tablesExist = Schema::hasTable('t_pages') &&
                       Schema::hasTable('t_page_blocks') &&
                       Schema::hasTable('t_page_block_types');

        $menusTablesExist = Schema::hasTable('t_menus') && Schema::hasTable('t_menu_items');

        try {
            if (!$tablesExist) {
                $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/pages';
                Artisan::call('migrate', [
                    '--path' => $migrationsPath,
                    '--force' => true
                ]);
                $this->info('✓ Pages migrations completed successfully');
            } else {
                $this->info('✓ Pages tables already exist');
            }

            if (!$menusTablesExist) {
                $migrationsPath = str_replace(base_path() . '/', '', $packagePath) . '/database/migrations/menus';
                Artisan::call('migrate', [
                    '--path' => $migrationsPath,
                    '--force' => true
                ]);
                $this->info('✓ Menus migrations completed successfully');
            } else {
                $this->info('✓ Menus tables already exist');
            }
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // Step 2: Seed Default Block Types
        $this->info('Step 2: Creating default block types...');
        $this->seedDefaultBlockTypes();
        $this->info('✓ Default block types created');
        $this->newLine();

        // Step 3: Build Frontend Assets
        $this->info('Step 3: Building frontend assets...');
        if (file_exists($packagePath . '/package.json')) {
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

        // Step 4: Publish Assets
        $this->info('Step 4: Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'holart-cms-assets',
            '--force' => true,
        ]);
        $this->info('✓ Assets published successfully');
        $this->newLine();

        // Step 5: Clear Cache
        $this->info('Step 5: Clearing application cache...');
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
