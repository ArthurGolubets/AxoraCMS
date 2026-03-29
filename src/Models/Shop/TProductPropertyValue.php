<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TProductPropertyValue extends Model
{
    protected $table = 't_product_property_values';

    protected $fillable = [
        'product_id',
        'property_id',
        'value',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'property_id' => 'integer',
    ];

    /**
     * Get the product that owns the property value
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(TProduct::class, 'product_id');
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
