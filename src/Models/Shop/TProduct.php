<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TProduct extends Model
{
    protected $table = 't_products';

    protected $fillable = [
        'catalog_id',
        'name',
        'slug',
        'title',
        'description',
        'keywords',
        'price',
        'old_price',
        'sku',
        'main_image',
        'tags',
        'is_new',
        'is_hot',
        'is_recommended',
        'is_active',
        'content',
        'gallery',
    ];

    protected $casts = [
        'catalog_id' => 'integer',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_new' => 'boolean',
        'is_hot' => 'boolean',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'array',
        'gallery' => 'array',
    ];

    /**
     * Get the category that owns the product
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(TCatalog::class, 'catalog_id');
    }

    /**
     * Get product variants
     */
    public function variants(): HasMany
    {
        return $this->hasMany(TProductVariant::class, 'product_id');
    }

    /**
     * Check if product has variants
     */
    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    /**
     * Get filter values assigned to this product
     */
    public function filterValues()
    {
        return $this->belongsToMany(
            'HolartWeb\HolartCMS\Models\Shop\TFilterValue',
            't_product_filter_values',
            'product_id',
            'filter_value_id'
        )->withTimestamps();
    }

    /**
     * Get filters with values for this product
     */
    public function getFiltersWithValues()
    {
        $filterValues = $this->filterValues()->with('filter')->get();

        $filters = [];
        foreach ($filterValues as $filterValue) {
            $filterId = $filterValue->filter->id;
            if (!isset($filters[$filterId])) {
                $filters[$filterId] = [
                    'filter' => $filterValue->filter,
                    'values' => []
                ];
            }
            $filters[$filterId]['values'][] = $filterValue;
        }

        return array_values($filters);
    }

    /**
     * Sync filter values for this product
     */
    public function syncFilterValues(array $filterValueIds)
    {
        // Get filter_id for each filter_value_id
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TFilterValue')) {
            $filterValueClass = 'HolartWeb\HolartCMS\Models\Shop\TFilterValue';
            $filterValues = $filterValueClass::whereIn('id', $filterValueIds)->get();

            $syncData = [];
            foreach ($filterValues as $filterValue) {
                $syncData[$filterValue->id] = ['filter_id' => $filterValue->filter_id];
            }

            $this->filterValues()->sync($syncData);
        } else {
            $this->filterValues()->sync($filterValueIds);
        }
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->old_price || $this->old_price <= $this->price) {
            return null;
        }

        return round((($this->old_price - $this->price) / $this->old_price) * 100);
    }

    /**
     * Generate unique slug
     */
    public static function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $count = 1;
        $originalSlug = $slug;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
