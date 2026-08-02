<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TProductVariantPropertyValue extends Model
{
    protected $table = 't_product_variant_property_values';

    protected $fillable = [
        'variant_id',
        'property_id',
        'value',
    ];

    protected $casts = [
        'variant_id' => 'integer',
        'property_id' => 'integer',
    ];

    /**
     * Get the variant that owns the property value
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(TProductVariant::class, 'variant_id');
    }

    /**
     * Get the property definition
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(TCatalogProperty::class, 'property_id');
    }

    /**
     * Get typed value based on property type
     */
    public function getTypedValue()
    {
        if (!$this->property) {
            return $this->value;
        }

        return match($this->property->type) {
            'number' => is_numeric($this->value) ? (float)$this->value : null,
            'text', 'string' => (string)$this->value,
            default => $this->value,
        };
    }
}
