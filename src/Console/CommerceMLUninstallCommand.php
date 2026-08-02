<?php

namespace HolartWeb\AxoraCMS\Console;

use Illuminate\Console\Command;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommerceMLUninstallCommand extends Command
{
    const MODULE_NAME = 'commerceml';

    protected $signature = 'axoracms:commerceml-uninstall {--preserve-db : Preserve database tables and data}';
    protected $description = 'Uninstall AxoraCMS CommerceML Integration Module';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ CommerceML Integration Uninstaller ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $preserveDb = $this->option('preserve-db');

        if (!$preserveDb) {
            $this->warn('⚠ WARNING: This will remove CommerceML fields from products and catalogs!');
            if ($this->input->isInteractive() && defined('STDIN')) {
                if (!$this->confirm('Are you sure you want to continue?', false)) {
                    $this->info('Uninstallation cancelled.');
                    return self::SUCCESS;
                }
            } else {
                $this->info('Running in non-interactive mode, proceeding with uninstallation...');
            }
        }

        // Step 1: Handle Database
        if (!$preserveDb) {
            $this->info('Step 1: Removing database tables and columns...');

            try {
                Schema::disableForeignKeyConstraints();

                // Drop CommerceML settings table
                if (Schema::hasTable('t_commerceml_settings')) {
                    Schema::dropIfExists('t_commerceml_settings');
                    $this->info('✓ Dropped t_commerceml_settings table');
                }

                // Remove columns from products table
                if (Schema::hasTable('t_products')) {
                    if (Schema::hasColumn('t_products', '1c_id')) {
                        Schema::table('t_products', function ($table) {
                            $table->dropColumn('1c_id');
                        });
                        $this->info('✓ Removed 1c_id from t_products');
                    }
                    if (Schema::hasColumn('t_products', 'quantity')) {
                        Schema::table('t_products', function ($table) {
                            $table->dropColumn('quantity');
                        });
                        $this->info('✓ Removed quantity from t_products');
                    }
                }

                // Remove columns from catalogs table
                if (Schema::hasTable('t_catalogs')) {
                    if (Schema::hasColumn('t_catalogs', '1c_id')) {
                        Schema::table('t_catalogs', function ($table) {
                            $table->dropColumn('1c_id');
                        });
                        $this->info('✓ Removed 1c_id from t_catalogs');
                    }
                }

                Schema::enableForeignKeyConstraints();
            } catch (\Exception $e) {
                $this->error('❌ Error removing database tables: ' . $e->getMessage());
            }
            $this->newLine();

            // Step 2: Remove Migration Records
            $this->info('Step 2: Removing migration records from database...');
            $migrationFiles = [
                '2026_08_02_173050_add_1c_fields_to_products_table',
                '2026_08_02_173057_add_1c_id_to_catalogs_table',
                '2026_08_02_173144_create_t_commerceml_settings_table',
            ];

            try {
                DB::table('migrations')->whereIn('migration', $migrationFiles)->delete();
                $this->info('✓ Removed migration records from database');
            } catch (\Exception $e) {
                $this->warn('⚠ Could not remove migration records: ' . $e->getMessage());
            }
            $this->newLine();
        } else {
            $this->info('Step 1: Preserving database tables and data...');
            $this->info('✓ Database preserved');
            $this->newLine();
        }

        // Step 3: Clear Cache
        $this->info('Step 3: Clearing application cache...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared successfully');
        $this->newLine();

        // Remove module record from database
        $this->info('Removing module registration...');
        TModule::uninstall(self::MODULE_NAME);
        $this->info('✓ Module unregistered');
        $this->newLine();

        $this->info('╔══════════════════════════════════════╗');
        $this->info('║ CommerceML Uninstalled Successfully!║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        if ($preserveDb) {
            $this->warn('Database tables were preserved. To completely remove CommerceML data,');
            $this->warn('run the command again without the --preserve-db option.');
        }

        return self::SUCCESS;
    }
}
