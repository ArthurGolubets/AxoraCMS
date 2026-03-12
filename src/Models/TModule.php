<?php

namespace HolartWeb\HolartCMS\Models;

use Illuminate\Database\Eloquent\Model;

class TModule extends Model
{
    protected $table = 't_modules';

    protected $fillable = [
        'module_name',
        'version',
        'installed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];

    /**
     * Register module installation
     */
    public static function install(string $moduleName, string $version): void
    {
        static::updateOrCreate(
            ['module_name' => $moduleName],
            ['version' => $version, 'installed_at' => now()]
        );
    }

    /**
     * Remove module record
     */
    public static function uninstall(string $moduleName): void
    {
        static::where('module_name', $moduleName)->delete();
    }

    /**
     * Get installed module version
     */
    public static function getInstalledVersion(string $moduleName): ?string
    {
        $module = static::where('module_name', $moduleName)->first();
        return $module?->version;
    }

    /**
     * Check if module is installed
     */
    public static function isInstalled(string $moduleName): bool
    {
        return static::where('module_name', $moduleName)->exists();
    }

    /**
     * Update module version
     */
    public static function updateVersion(string $moduleName, string $version): void
    {
        static::where('module_name', $moduleName)->update(['version' => $version]);
    }
}
