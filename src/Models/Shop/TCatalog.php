<?php

namespace HolartWeb\HolartCMS\Models\Shop;

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
