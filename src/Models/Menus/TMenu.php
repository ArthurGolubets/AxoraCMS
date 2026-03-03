<?php

namespace HolartWeb\HolartCMS\Models\Menus;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TMenu extends Model
{
    protected $table = 't_menus';

    protected $fillable = [
        'name',
        'code',
        'location',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const LOCATION_HEADER = 'header';
    const LOCATION_FOOTER = 'footer';

    /**
     * Get all menu items
     */
    public function items()
    {
        return $this->hasMany(TMenuItem::class, 'menu_id')->orderBy('sort');
    }

    /**
     * Get root menu items (without parent)
     */
    public function rootItems()
    {
        return $this->hasMany(TMenuItem::class, 'menu_id')
            ->whereNull('parent_id')
            ->orderBy('sort');
    }

    /**
     * Generate unique code from name
     */
    public static function generateCode(string $name, ?int $excludeId = null): string
    {
        $code = Str::slug($name, '_');

        $originalCode = $code;
        $counter = 1;

        $query = static::where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
            $query = static::where('code', $code);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $code;
    }

    /**
     * Get menu by code
     */
    public static function getByCode(string $code)
    {
        return static::where('code', $code)
            ->where('is_active', true)
            ->with(['rootItems' => function ($query) {
                $query->where('is_active', true)
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('sort');
                    }]);
            }])
            ->first();
    }

    /**
     * Get menus by location
     */
    public static function getByLocation(string $location)
    {
        return static::where('location', $location)
            ->where('is_active', true)
            ->with(['rootItems' => function ($query) {
                $query->where('is_active', true)
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('sort');
                    }]);
            }])
            ->get();
    }
}
