<?php

namespace HolartWeb\HolartCMS\Models\Commerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TOrderItems extends Model
{
    protected $table = 't_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'amount',
        'total_price'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'amount' => 'integer',
        'total_price' => 'decimal:2',
    ];

    /**
     * Заказ к которому относится товар
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TOrders::class, 'order_id');
    }

    /**
     * Товар
     */
    public function product(): ?BelongsTo
    {
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            return $this->belongsTo(\HolartWeb\HolartCMS\Models\Shop\TProduct::class, 'product_id');
        }
        return null;
    }
}
