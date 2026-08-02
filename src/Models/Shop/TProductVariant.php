<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TProductVariant extends Model
{
    protected $table = 't_product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'old_price',
        'attributes',
        'image',
        'description',
        'addition_info',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'attributes' => 'array',
        'addition_info' => 'array',
    ];

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(TProduct::class, 'product_id');
    }

    /**
     * Get property values for this variant
     */
    public function propertyValues(): HasMany
    {
        return $this->hasMany(TProductVariantPropertyValue::class, 'variant_id');
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
}
