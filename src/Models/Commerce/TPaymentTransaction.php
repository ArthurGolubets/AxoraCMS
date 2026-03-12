<?php

namespace HolartWeb\HolartCMS\Models\Commerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TPaymentTransaction extends Model
{
    protected $table = 't_payment_transactions';

    protected $fillable = [
        'transaction_id',
        'order_id',
        'link',
        'status'
    ];

    protected $casts = [
        'order_id' => 'integer',
    ];

    // Константы для status
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_CANCEL = 'cancel';

    /**
     * Заказ связанный с транзакцией
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TOrders::class, 'order_id');
    }

    /**
     * Проверка успешности транзакции
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Проверка ожидания транзакции
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Проверка отмены транзакции
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCEL;
    }
}
