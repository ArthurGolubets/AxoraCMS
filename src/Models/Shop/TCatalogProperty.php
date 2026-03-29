<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TCatalogProperty extends Model
{
    protected $table = 't_catalog_properties';

    protected $fillable = [
        'catalog_id',
        'code',
        'name',
        'type',
        'is_multiple',
        'sort_order',
    ];

    protected $casts = [
        'catalog_id' => 'integer',
        'is_multiple' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the catalog that owns the property
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(TCatalog::class, 'catalog_id');
    }

    /**
     * Get property values
     */
    public function propertyValues(): HasMany
    {
        return $this->hasMany(TProductPropertyValue::class, 'property_id');
    }
}
