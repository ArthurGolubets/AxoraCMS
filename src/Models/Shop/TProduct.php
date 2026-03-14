<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

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
        'addition_info'
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
        'addition_info' => 'array',
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
     * Get comments for this product
     */
    public function comments(): HasMany
    {
        if (class_exists('HolartWeb\AxoraCMS\Models\Callback\TComments')) {
            return $this->hasMany('HolartWeb\AxoraCMS\Models\Callback\TComments', 'product_id');
        }
        return $this->hasMany(Model::class, 'product_id');
    }

    /**
     * Get moderated comments for this product
     */
    public function moderatedComments(): HasMany
    {
        return $this->comments()->where('is_moderated', true);
    }

    /**
     * Get filter values assigned to this product
     */
    public function filterValues()
    {
        return $this->belongsToMany(
            'HolartWeb\AxoraCMS\Models\Shop\TFilterValue',
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
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TFilterValue')) {
            $filterValueClass = 'HolartWeb\AxoraCMS\Models\Shop\TFilterValue';
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

    /**
     * Get average rating for this product
     */
    public function getAverageRating(): ?float
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Callback\TComments')) {
            return null;
        }

        $avgRating = $this->moderatedComments()
            ->whereNotNull('rating')
            ->avg('rating');

        return $avgRating ? round($avgRating, 1) : null;
    }

    /**
     * Get rating statistics for this product
     */
    public function getRatingStats(): array
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Callback\TComments')) {
            return [
                'average' => null,
                'count' => 0,
                'distribution' => [],
            ];
        }

        $comments = $this->moderatedComments()
            ->whereNotNull('rating')
            ->get();

        if ($comments->isEmpty()) {
            return [
                'average' => null,
                'count' => 0,
                'distribution' => [],
            ];
        }

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($comments as $comment) {
            if ($comment->rating >= 1 && $comment->rating <= 5) {
                $distribution[$comment->rating]++;
            }
        }

        return [
            'average' => round($comments->avg('rating'), 1),
            'count' => $comments->count(),
            'distribution' => $distribution,
        ];
    }

    /**
     * Get rating attribute (alias for average rating)
     */
    public function getRatingAttribute(): ?float
    {
        return $this->getAverageRating();
    }
}
