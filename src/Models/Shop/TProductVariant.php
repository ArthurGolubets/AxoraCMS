<?php

namespace HolartWeb\HolartCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'attributes' => 'array',
    ];

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(TProduct::class, 'product_id');
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
