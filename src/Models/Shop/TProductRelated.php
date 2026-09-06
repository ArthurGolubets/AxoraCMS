<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single "companion product" link (part of a set).
 */
class TProductRelated extends Model
{
    protected $table = 't_product_related';

    protected $fillable = [
        'product_id',
        'variant_sku',
        'related_product_id',
        'related_variant_sku',
        'sort',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'related_product_id' => 'integer',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(TProduct::class, 'product_id');
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(TProduct::class, 'related_product_id');
    }

    /**
     * Resolve the related variant model (if this link targets a specific variant).
     */
    public function resolveRelatedVariant(): ?TProductVariant
    {
        if (! $this->related_variant_sku) {
            return null;
        }

        return TProductVariant::where('product_id', $this->related_product_id)
            ->where('sku', $this->related_variant_sku)
            ->first();
    }
}
