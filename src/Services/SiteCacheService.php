<?php

namespace HolartWeb\AxoraCMS\Services;

use Closure;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Models\TPanelSettings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * "Кеширование" module: caches what the site reads on every request
 * (table existence checks, project settings/menus, page SEO data, catalog and
 * info block queries, optionally the whole HTML page for guests).
 *
 * Every cache entry belongs to a group. A group is invalidated by bumping its
 * version (works on every cache store, no tags needed). Module settings live in
 * t_panel_settings ("cache_*") and are mirrored to a small PHP file so reading
 * them costs no database or cache round trip.
 */
class SiteCacheService
{
    public const MODULE_NAME = 'cache';

    public const GROUP_SCHEMA = 'schema';

    public const GROUP_SETTINGS = 'settings';

    public const GROUP_PAGES = 'pages';

    public const GROUP_CATALOG = 'catalog';

    public const GROUP_INFOBLOCKS = 'infoblocks';

    public const GROUP_HTML = 'html';

    public const GROUPS = [
        self::GROUP_SCHEMA,
        self::GROUP_SETTINGS,
        self::GROUP_PAGES,
        self::GROUP_CATALOG,
        self::GROUP_INFOBLOCKS,
        self::GROUP_HTML,
    ];

    /**
     * Groups holding site content (everything except the table-existence map).
     */
    public const CONTENT_GROUPS = [
        self::GROUP_SETTINGS,
        self::GROUP_PAGES,
        self::GROUP_CATALOG,
        self::GROUP_INFOBLOCKS,
        self::GROUP_HTML,
    ];

    public const VISITS_SYNC = 'sync';

    public const VISITS_DEFERRED = 'deferred';

    public const VISITS_OFF = 'off';

    /**
     * Paths that must never be served from the full-page cache by default.
     */
    public const DEFAULT_HTML_EXCLUDED = "cart*\ncheckout*\norder*\naccount*\nprofile*\ncabinet*\nlogin*\nlogout*\nregister*\npassword*\nfavorites*\nsearch*";

    /**
     * Default module settings (t_panel_settings key => [value, type]).
     *
     * @var array<string, array{0: mixed, 1: string}>
     */
    public const DEFAULT_SETTINGS = [
        'cache_enabled' => [true, 'boolean'],
        'cache_schema' => [true, 'boolean'],
        'cache_settings' => [true, 'boolean'],
        'cache_pages' => [true, 'boolean'],
        'cache_catalog' => [true, 'boolean'],
        'cache_infoblocks' => [true, 'boolean'],
        'cache_html' => [false, 'boolean'],
        'cache_html_query' => [true, 'boolean'],
        'cache_html_excluded' => [self::DEFAULT_HTML_EXCLUDED, 'string'],
        'cache_ttl' => [60, 'integer'],
        'cache_html_ttl' => [10, 'integer'],
        'cache_store' => ['', 'string'],
        'cache_visits_mode' => [self::VISITS_DEFERRED, 'string'],
    ];

    private const KEY_PREFIX = 'axora-cms:cache:';

    /**
     * Model namespace prefix => cache groups invalidated when such a model changes.
     *
     * @var array<string, array<int, string>>
     */
    private const MODEL_GROUPS = [
        'HolartWeb\\AxoraCMS\\Models\\Shop\\' => [self::GROUP_CATALOG, self::GROUP_PAGES, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\InfoBlocks\\' => [self::GROUP_INFOBLOCKS, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\Menus\\' => [self::GROUP_SETTINGS, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\SEO\\' => [self::GROUP_PAGES, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\Pages\\' => [self::GROUP_PAGES, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\Callback\\' => [self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\Integrations\\' => [self::GROUP_SETTINGS],
        'HolartWeb\\AxoraCMS\\Models\\TPanelSettings' => [self::GROUP_SETTINGS, self::GROUP_PAGES, self::GROUP_HTML],
        'HolartWeb\\AxoraCMS\\Models\\TPanelCustomField' => [self::GROUP_SETTINGS, self::GROUP_HTML],
    ];

    /**
     * Models whose writes never affect what the site renders (visit log, counters).
     *
     * @var array<int, string>
     */
    private const IGNORED_MODELS = [
        'HolartWeb\\AxoraCMS\\Models\\SEO\\TPageVisit',
    ];

    /**
     * Attributes that change on every visit and must not invalidate the cache.
     *
     * @var array<int, string>
     */
    private const IGNORED_ATTRIBUTES = ['views_count', 'views', 'updated_at'];

    /** @var array{installed: bool, settings: array<string, mixed>}|null */
    private ?array $config = null;

    /** @var array<string, int>|null */
    private ?array $versions = null;

    /** @var array<string, bool>|null */
    private ?array $tables = null;

    /** @var array<string, mixed>|null */
    private ?array $settingsMap = null;

    /**
     * Module config (installed flag + settings), read from the mirror file.
     *
     * @return array{installed: bool, settings: array<string, mixed>}
     */
    public function config(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $path = $this->configPath();

        if (is_file($path)) {
            $config = @include $path;

            if (is_array($config) && isset($config['installed'], $config['settings'])) {
                return $this->config = $config;
            }
        }

        return $this->config = $this->rebuildConfig();
    }

    /**
     * Re-read module state from the database and rewrite the mirror file.
     *
     * @return array{installed: bool, settings: array<string, mixed>}
     */
    public function rebuildConfig(): array
    {
        $config = ['installed' => false, 'settings' => $this->defaultSettings()];

        try {
            if (Schema::hasTable('t_modules')) {
                $config['installed'] = TModule::where('module_name', self::MODULE_NAME)->exists();
            }

            if ($config['installed'] && Schema::hasTable('t_panel_settings')) {
                $stored = TPanelSettings::whereIn('key', array_keys(self::DEFAULT_SETTINGS))->get();

                foreach ($stored as $setting) {
                    $config['settings'][$setting->key] = TPanelSettings::castValue($setting->value, (string) $setting->type);
                }
            }
        } catch (Throwable) {
            // No database yet (package discovery, fresh install) — module is off.
            return $this->config = $config;
        }

        $this->writeConfigFile($config);
        $this->versions = null;

        return $this->config = $config;
    }

    public function isInstalled(): bool
    {
        return (bool) $this->config()['installed'];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return $this->config()['settings'] + $this->defaultSettings();
    }

    public function setting(string $key): mixed
    {
        return $this->settings()[$key] ?? null;
    }

    /**
     * Whether caching of the given group is active for the current request.
     * Content groups are bypassed inside the admin panel so editors always see live data.
     */
    public function enabled(string $group): bool
    {
        if (! $this->isInstalled() || ! $this->setting('cache_enabled')) {
            return false;
        }

        if ($group !== self::GROUP_SCHEMA && $this->isAdminRequest()) {
            return false;
        }

        return (bool) $this->setting('cache_'.$group);
    }

    /**
     * How page visits are recorded: right away, after the response is sent, or not at all.
     */
    public function visitsMode(): string
    {
        if (! $this->isInstalled() || ! $this->setting('cache_enabled')) {
            return self::VISITS_SYNC;
        }

        $mode = (string) $this->setting('cache_visits_mode');

        return in_array($mode, [self::VISITS_SYNC, self::VISITS_DEFERRED, self::VISITS_OFF], true) ? $mode : self::VISITS_SYNC;
    }

    /**
     * Cache a value inside a group. Falls through to the callback when the group is disabled.
     * Null results are cached too; paginators get the current request path back.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $group, string $key, Closure $callback, ?int $ttlMinutes = null): mixed
    {
        if (! $this->enabled($group)) {
            return $callback();
        }

        $cacheKey = $this->key($group, $key);

        try {
            $cached = $this->store()->get($cacheKey);
        } catch (Throwable) {
            return $callback();
        }

        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $this->restore($cached['value']);
        }

        $value = $callback();

        try {
            $this->store()->put($cacheKey, ['value' => $value], $this->ttlSeconds($ttlMinutes));
        } catch (Throwable) {
            // Unserializable value or store failure: serve the fresh value uncached.
        }

        return $value;
    }

    /**
     * Read a raw value from a group (used by the full-page cache).
     */
    public function get(string $group, string $key): mixed
    {
        if (! $this->enabled($group)) {
            return null;
        }

        try {
            return $this->store()->get($this->key($group, $key));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Write a raw value to a group (used by the full-page cache).
     */
    public function put(string $group, string $key, mixed $value, ?int $ttlMinutes = null): void
    {
        if (! $this->enabled($group)) {
            return;
        }

        try {
            $this->store()->put($this->key($group, $key), $value, $this->ttlSeconds($ttlMinutes));
        } catch (Throwable) {
            // Ignore — the page is simply not cached.
        }
    }

    /**
     * Schema::hasTable() replacement: answers from a per-request memo and, when the
     * module is on, from one cached map instead of an information_schema query per call.
     */
    public function hasTable(string $table): bool
    {
        if ($this->tables === null) {
            $this->tables = [];

            if ($this->enabled(self::GROUP_SCHEMA)) {
                try {
                    $this->tables = (array) ($this->store()->get($this->key(self::GROUP_SCHEMA, 'tables')) ?? []);
                } catch (Throwable) {
                    $this->tables = [];
                }
            }
        }

        if (array_key_exists($table, $this->tables)) {
            return $this->tables[$table];
        }

        $exists = Schema::hasTable($table);
        $this->tables[$table] = $exists;

        if ($this->enabled(self::GROUP_SCHEMA)) {
            try {
                $this->store()->put($this->key(self::GROUP_SCHEMA, 'tables'), $this->tables, 86400);
            } catch (Throwable) {
                // Memo still saves repeated checks within this request.
            }
        }

        return $exists;
    }

    /**
     * All project settings (t_panel_settings) as a key => cast value map, cached as one entry.
     *
     * @return array<string, mixed>|null null when the settings cache is off
     */
    public function settingsMap(): ?array
    {
        if (! $this->enabled(self::GROUP_SETTINGS)) {
            return null;
        }

        return $this->settingsMap ??= $this->remember(self::GROUP_SETTINGS, 'panel_settings', fn () => TPanelSettings::all_settings());
    }

    /**
     * Invalidate cache groups (all of them by default).
     *
     * @param  array<int, string>|null  $groups
     */
    public function flush(?array $groups = null): void
    {
        $groups ??= self::GROUPS;

        if (in_array(self::GROUP_SCHEMA, $groups, true)) {
            $this->tables = null;
        }

        if (in_array(self::GROUP_SETTINGS, $groups, true)) {
            $this->settingsMap = null;
        }

        // Bumped even while the module is off, so nothing stale survives a reinstall.
        try {
            $versions = $this->versions();
            foreach ($groups as $group) {
                $versions[$group] = ($versions[$group] ?? 1) + 1;
            }
            $this->store()->forever(self::KEY_PREFIX.'versions', $versions);
            $this->versions = $versions;
        } catch (Throwable) {
            // Store unavailable — entries will expire by TTL.
        }
    }

    /**
     * React to an Eloquent "saved"/"deleted" event of a CMS model.
     */
    public function handleModelChange(object $model, bool $deleted = false): void
    {
        if (! $model instanceof Model) {
            return;
        }

        $class = $model::class;

        if ($class === TModule::class) {
            $this->rebuildConfig();
            $this->flush();

            return;
        }

        if (! $this->isInstalled()) {
            return;
        }

        if (in_array($class, self::IGNORED_MODELS, true)) {
            return;
        }

        if (! $deleted && ! $model->wasRecentlyCreated) {
            $changed = array_diff(array_keys($model->getChanges()), self::IGNORED_ATTRIBUTES);
            if ($changed === []) {
                return;
            }
        }

        if ($class === TPanelSettings::class && str_starts_with((string) $model->getAttribute('key'), 'cache_')) {
            $this->rebuildConfig();
        }

        foreach (self::MODEL_GROUPS as $prefix => $groups) {
            if ($class === $prefix || str_starts_with($class, $prefix)) {
                $this->flush($groups);

                return;
            }
        }
    }

    /**
     * Persist module settings and refresh the mirror file.
     *
     * @param  array<string, mixed>  $values
     */
    public function saveSettings(array $values): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => [$default, $type]) {
            if (array_key_exists($key, $values)) {
                TPanelSettings::set($key, $values[$key], $type);
            }
        }

        $this->rebuildConfig();
        $this->flush();
    }

    /**
     * Seed default settings on install (keeps values that already exist).
     */
    public function seedDefaultSettings(): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => [$default, $type]) {
            if (! TPanelSettings::where('key', $key)->exists()) {
                $value = $key === 'cache_store' ? $this->defaultStoreName() : $default;
                TPanelSettings::set($key, $value, $type);
            }
        }
    }

    public function removeSettings(): void
    {
        TPanelSettings::whereIn('key', array_keys(self::DEFAULT_SETTINGS))->delete();
    }

    /**
     * Cache stores configured in config/cache.php that can hold data between requests.
     *
     * @return array<int, array{name: string, driver: string}>
     */
    public function availableStores(): array
    {
        $stores = [];

        foreach ((array) config('cache.stores', []) as $name => $store) {
            $driver = $store['driver'] ?? '';
            if (in_array($driver, ['array', 'null'], true)) {
                continue;
            }
            $stores[] = ['name' => (string) $name, 'driver' => (string) $driver];
        }

        return $stores;
    }

    /**
     * Name of the store actually used for module data.
     */
    public function storeName(): string
    {
        $configured = (string) $this->setting('cache_store');

        if ($configured !== '' && config("cache.stores.{$configured}") !== null) {
            return $configured;
        }

        return (string) config('cache.default');
    }

    /**
     * Preferred store on install: "file" (no database round trips) when configured.
     */
    public function defaultStoreName(): string
    {
        return config('cache.stores.file') !== null ? 'file' : (string) config('cache.default');
    }

    public function configPath(): string
    {
        // Per environment, so running the test suite never rewrites the live site's state.
        return (string) config(
            'axora-cms.site_cache_config_path',
            storage_path('framework/axora-cms-cache.'.app()->environment().'.php'),
        );
    }

    /**
     * Forget everything memoized for this request (after settings change, in tests).
     */
    public function resetState(): void
    {
        $this->config = null;
        $this->versions = null;
        $this->tables = null;
        $this->settingsMap = null;
    }

    private function store(): Repository
    {
        return Cache::store($this->storeName());
    }

    private function key(string $group, string $key): string
    {
        $version = $this->versions()[$group] ?? 1;

        return self::KEY_PREFIX.$group.':'.$version.':'.md5($key);
    }

    /**
     * @return array<string, int>
     */
    private function versions(): array
    {
        if ($this->versions !== null) {
            return $this->versions;
        }

        try {
            return $this->versions = (array) ($this->store()->get(self::KEY_PREFIX.'versions') ?? []);
        } catch (Throwable) {
            return $this->versions = [];
        }
    }

    private function ttlSeconds(?int $ttlMinutes): int
    {
        $minutes = $ttlMinutes ?? (int) $this->setting('cache_ttl');

        return max(1, $minutes) * 60;
    }

    /**
     * Paginators keep the URL path of the request that built them; point them at the current one.
     */
    private function restore(mixed $value): mixed
    {
        if ($value instanceof PaginatorContract && method_exists($value, 'withPath')) {
            $value->withPath(Paginator::resolveCurrentPath());
        }

        return $value;
    }

    private function isAdminRequest(): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        $prefix = trim((string) config('axora-cms.route_prefix', 'admin'), '/');
        $request = request();

        return $request->is($prefix) || $request->is($prefix.'/*');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(): array
    {
        return array_map(fn (array $definition) => $definition[0], self::DEFAULT_SETTINGS);
    }

    /**
     * @param  array{installed: bool, settings: array<string, mixed>}  $config
     */
    private function writeConfigFile(array $config): void
    {
        $path = $this->configPath();

        try {
            $directory = dirname($path);
            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }

            $temporary = $path.'.'.getmypid().'.tmp';
            file_put_contents($temporary, '<?php return '.var_export($config, true).';'.PHP_EOL);
            rename($temporary, $path);

            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($path, true);
            }
        } catch (Throwable) {
            // Read-only storage: config is rebuilt from the database on each request.
        }
    }
}
