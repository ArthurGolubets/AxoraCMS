<?php

namespace HolartWeb\HolartCMS\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

class TIntegrationSettings extends Model
{
    protected $table = 't_integration_settings';

    protected $fillable = [
        'integration_type',
        'key',
        'value',
        'type',
    ];

    /**
     * Get setting value by integration type and key.
     */
    public static function get(string $integrationType, string $key, mixed $default = null): mixed
    {
        $setting = static::where('integration_type', $integrationType)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value by integration type and key.
     */
    public static function set(string $integrationType, string $key, mixed $value, string $type = 'string'): void
    {
        $stringValue = static::prepareValue($value, $type);

        static::updateOrCreate(
            [
                'integration_type' => $integrationType,
                'key' => $key
            ],
            ['value' => $stringValue, 'type' => $type]
        );
    }

    /**
     * Get all settings for a specific integration as key-value array.
     */
    public static function getAll(string $integrationType): array
    {
        $settings = static::where('integration_type', $integrationType)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = static::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type.
     */
    protected static function castValue(mixed $value, string $type): mixed
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
