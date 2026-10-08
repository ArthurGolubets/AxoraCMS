<?php

namespace HolartWeb\AxoraCMS\Models;

use HolartWeb\AxoraCMS\Services\SiteCacheService;
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

        static::refreshSiteCache();
    }

    /**
     * Remove module record
     */
    public static function uninstall(string $moduleName): void
    {
        static::where('module_name', $moduleName)->delete();

        static::refreshSiteCache();
    }

    /**
     * Module set changed: tables, routes and menus differ now — drop every cached answer.
     */
    protected static function refreshSiteCache(): void
    {
        $siteCache = app(SiteCacheService::class);
        $siteCache->rebuildConfig();
        $siteCache->flush();
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
