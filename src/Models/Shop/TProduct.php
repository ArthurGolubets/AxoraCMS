<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use HolartWeb\AxoraCMS\Services\EntityLinkResolver;
use HolartWeb\AxoraCMS\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        '1c_id',
        'quantity',
        'main_image',
        'tags',
        'is_new',
        'is_hot',
        'is_recommended',
        'is_active',
        'content',
        'gallery',
        'addition_info',
        'range_filter_values',
        'entity_filter_values',
        'string_filter_values',
    ];

    protected $casts = [
        'catalog_id' => 'integer',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'quantity' => 'integer',
        'is_new' => 'boolean',
        'is_hot' => 'boolean',
        'is_recommended' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'array',
        'addition_info' => 'array',
        'gallery' => 'array',
        'range_filter_values' => 'array',
        'entity_filter_values' => 'array',
        'string_filter_values' => 'array',
    ];

    /**
     * Rich-text content comes from the admin editor, the API and 1C imports.
     * Sanitize it on write so untrusted markup can never reach the database,
     * regardless of the code path that stores the model.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null ? null : HtmlSanitizer::clean($value),
        );
    }

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
     * Get property values for this product
     */
    public function propertyValues(): HasMany
    {
        return $this->hasMany(TProductPropertyValue::class, 'product_id');
    }

    /**
     * Companion-product links owned by this product (сопутствующие товары / наборы).
     */
    public function relatedLinks(): HasMany
    {
        return $this->hasMany(TProductRelated::class, 'product_id')->orderBy('sort');
    }

    /**
     * Companion products for this product, optionally scoped to one variant SKU.
     * Product-level links (variant_sku = null) always apply.
     *
     * @return Collection<int, array{link: TProductRelated, product: TProduct, variant: ?TProductVariant}>
     */
    public function companionProducts(?string $variantSku = null)
    {
        return $this->relatedLinks()
            ->where(function ($query) use ($variantSku) {
                $query->whereNull('variant_sku');
                if ($variantSku !== null) {
                    $query->orWhere('variant_sku', $variantSku);
                }
            })
            ->with('relatedProduct')
            ->get()
            ->map(fn (TProductRelated $link) => [
                'link' => $link,
                'product' => $link->relatedProduct,
                'variant' => $link->resolveRelatedVariant(),
            ])
            ->filter(fn ($row) => $row['product'] !== null)
            ->values();
    }

    /**
     * Get property values with their definitions
     */
    public function getPropertiesWithValues()
    {
        $catalog = $this->catalog;
        if (! $catalog) {
            return collect();
        }

        // Get all available properties from catalog hierarchy
        $availableProperties = $catalog->getAllProperties();

        // Get filled property values
        $filledValues = $this->propertyValues()->with('property')->get()->keyBy('property_id');

        // Combine properties with their values
        return $availableProperties->map(function ($property) use ($filledValues) {
            $value = $filledValues->get($property->id);

            return [
                'property' => $property,
                'value' => $value ? $value->value : null,
                'value_id' => $value ? $value->id : null,
            ];
        });
    }

    /**
     * Get formatted properties for display.
     *
     * Returns array of properties with name, code, type and values. For the
     * "entity" type the values are resolved into real models; for "table" the
     * value is the raw 2D array under the "table" key.
     *
     * Format: [['name' => ..., 'code' => ..., 'type' => ..., 'values' => [...]], ...]
     */
    public function getFormattedProperties(): array
    {
        $result = [];
        $resolver = new EntityLinkResolver;

        // Get property values with properties loaded
        $propertyValues = $this->propertyValues()->with('property')->get();

        foreach ($propertyValues as $pv) {
            $property = $pv->property;

            if (! $property) {
                continue;
            }

            $value = $pv->value;
            $decoded = json_decode($value, true);
            $decodedArray = ($decoded !== null && is_array($decoded)) ? $decoded : null;

            $row = [
                'name' => $property->name,
                'code' => $property->code,
                'type' => $property->type,
            ];

            if ($property->type === 'entity') {
                $row['values'] = $resolver->resolve($decodedArray ?? $value);
            } elseif ($property->type === 'table') {
                $row['values'] = [];
                $row['table'] = $decodedArray ?? [];
            } else {
                $row['values'] = $decodedArray ?? [$value];
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get this product's characteristic values, keyed by characteristic code,
     * with "entity" values resolved into real models.
     *
     * @return array<string, mixed>
     */
    public function getResolvedCharacteristics(): array
    {
        $raw = is_array($this->addition_info) ? $this->addition_info : [];

        if (empty($raw)) {
            return [];
        }

        $definitionClass = 'HolartWeb\AxoraCMS\Models\Shop\TCharacteristicDefinition';

        if (! class_exists($definitionClass)) {
            return $raw;
        }

        $definitions = $definitionClass::whereIn('applies_to', ['product', 'both'])
            ->get()
            ->keyBy('code');

        $resolver = new EntityLinkResolver;
        $result = [];

        foreach ($raw as $code => $value) {
            $definition = $definitions->get($code);

            if ($definition && $definition->type === 'entity') {
                $result[$code] = $resolver->resolve($value);
            } else {
                $result[$code] = $value;
            }
        }

        return $result;
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
            if (! isset($filters[$filterId])) {
                $filters[$filterId] = [
                    'filter' => $filterValue->filter,
                    'values' => [],
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
        if (! $this->old_price || $this->old_price <= $this->price) {
            return null;
        }

        return round((($this->old_price - $this->price) / $this->old_price) * 100);
    }

    /**
     * Generate unique slug
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 1;
        $originalSlug = $slug;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get average rating for this product
     */
    public function getAverageRating(): ?float
    {
        if (! class_exists('HolartWeb\AxoraCMS\Models\Callback\TComments')) {
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
        if (! class_exists('HolartWeb\AxoraCMS\Models\Callback\TComments')) {
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
