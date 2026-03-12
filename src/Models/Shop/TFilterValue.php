<?php

namespace HolartWeb\HolartCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class TFilterValue extends Model
{
    protected $table = 't_filter_values';

    protected $fillable = [
        'filter_id',
        'value',
        'code',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Get the filter this value belongs to
     */
    public function filter()
    {
        return $this->belongsTo(TFilter::class, 'filter_id');
    }

    /**
     * Get products that have this filter value
     */
    public function products()
    {
        return $this->belongsToMany(
            TProduct::class,
            't_product_filter_values',
            'filter_value_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Scope to get only active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
