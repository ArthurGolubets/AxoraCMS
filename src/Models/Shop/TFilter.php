<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class TFilter extends Model
{
    protected $table = 't_filters';

    protected $fillable = [
        'name',
        'code',
        'type',
        'catalog_id',
        'sort',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Get the catalog this filter belongs to (null for global filters)
     */
    public function catalog()
    {
        return $this->belongsTo(TCatalog::class, 'catalog_id');
    }

    /**
     * Get filter values
     */
    public function values()
    {
        return $this->hasMany(TFilterValue::class, 'filter_id')->orderBy('sort');
    }

    /**
     * Get active filter values
     */
    public function activeValues()
    {
        return $this->hasMany(TFilterValue::class, 'filter_id')
            ->where('is_active', true)
            ->orderBy('sort');
    }

    /**
     * Check if filter is global
     */
    public function isGlobal()
    {
        return $this->catalog_id === null;
    }

    /**
     * Scope to get only global filters
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('catalog_id');
    }

    /**
     * Scope to get filters for a specific catalog (including parent and children catalogs)
     */
    public function scopeForCatalog($query, $catalogId)
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return $query->whereNull('catalog_id');
        }

        $catalog = TCatalog::find($catalogId);
        if (!$catalog) {
            return $query->whereNull('catalog_id');
        }

        // Get current catalog and all parent catalog IDs
        $catalogIds = [$catalogId];
        $current = $catalog;
        while ($current->parent_id) {
            $catalogIds[] = $current->parent_id;
            $current = $current->parent;
        }

        // Add all children catalog IDs
        static::collectChildrenCatalogIds($catalog, $catalogIds);

        // Include global filters and filters from current, parent and children catalogs
        return $query->where(function($q) use ($catalogIds) {
            $q->whereNull('catalog_id')
              ->orWhereIn('catalog_id', $catalogIds);
        });
    }

    /**
     * Recursively collect all children catalog IDs
     */
    private static function collectChildrenCatalogIds($catalog, &$catalogIds)
    {
        $children = $catalog->children;
        foreach ($children as $child) {
            $catalogIds[] = $child->id;
            static::collectChildrenCatalogIds($child, $catalogIds);
        }
    }

    /**
     * Scope to get only active filters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate unique code
     */
    public static function generateCode($name, $excludeId = null)
    {
        $code = \Illuminate\Support\Str::slug($name, '_');
        $originalCode = $code;
        $counter = 1;

        while (true) {
            $query = static::where('code', $code);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $code = $originalCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }
}
