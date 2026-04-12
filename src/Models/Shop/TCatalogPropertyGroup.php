<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class TCatalogPropertyGroup extends Model
{
    protected $fillable = [
        'catalog_id',
        'code',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'catalog_id' => 'integer',
        'sort_order' => 'integer',
    ];


    public function catalog(): BelongsTo
    {
        return $this->belongsTo(TCatalog::class, 'catalog_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(TCatalogProperty::class, 'group_id')->orderBy('sort_order');
    }
}
