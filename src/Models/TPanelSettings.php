<?php

namespace HolartWeb\AxoraCMS\Models;

use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Database\Eloquent\Model;

class TPanelSettings extends Model
{
    protected $table = 't_panel_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Get setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // "Кеширование" module: all settings come from one cached map instead of a query per key.
        $cachedSettings = app(SiteCacheService::class)->settingsMap();
        if ($cachedSettings !== null) {
            return array_key_exists($key, $cachedSettings) ? $cachedSettings[$key] : $default;
        }

        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value by key.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stringValue = static::prepareValue($value, $type);

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );
    }

    /**
     * Get all settings as key-value array.
     */
    public static function all_settings(): array
    {
        $settings = static::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = static::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type.
     */
    public static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'array', 'json' => json_decode($value, true) ?? [],
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage.
     */
    protected static function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'array', 'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
