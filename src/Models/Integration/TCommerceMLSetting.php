<?php

namespace HolartWeb\AxoraCMS\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class TCommerceMLSetting extends Model
{
    protected $table = 't_commerceml_settings';

    protected $fillable = [
        'login',
        'password',
        'import_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the singleton instance of settings
     */
    public static function getSettings(): self
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'import_type' => 'separate',
                'is_enabled' => false,
            ]);
        }

        return $settings;
    }
}
