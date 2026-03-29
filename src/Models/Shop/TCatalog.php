<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TCatalog extends Model
{
    protected $table = 't_catalogs';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'title',
        'description',
        'keywords',
        'image',
        'content',
        'is_active',
        'addition_info',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'addition_info' => 'array',
    ];

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TCatalog::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(TCatalog::class, 'parent_id');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(TProduct::class, 'catalog_id');
    }

    /**
     * Get properties for this catalog
     */
    public function properties(): HasMany
    {
        return $this->hasMany(TCatalogProperty::class, 'catalog_id')->orderBy('sort_order');
    }

    /**
     * Get all properties including inherited from parent catalogs
     */
    public function getAllProperties()
    {
        $properties = collect();
        $catalog = $this;

        // Collect properties from current catalog and all parents
        while ($catalog) {
            $catalogProperties = $catalog->properties;
            foreach ($catalogProperties as $property) {
                // Add only if not already exists (child properties override parent)
                if (!$properties->contains('code', $property->code)) {
                    $properties->push($property);
                }
            }
            $catalog = $catalog->parent;
        }

        return $properties->sortBy('sort_order')->values();
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get breadcrumb path
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumbs, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
            $category = $category->parent;
        }

        return $breadcrumbs;
    }
}
