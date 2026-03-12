<?php

namespace HolartWeb\HolartCMS\Models\Commerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TPromocodes extends Model
{
    protected $table = 't_promocodes';

    protected $fillable = [
        'name',
        'code',
        'value',
        'type',
        'max_usage',
        'current_usage',
        'date_active_from',
        'date_active_to'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_usage' => 'integer',
        'current_usage' => 'integer',
        'date_active_from' => 'datetime',
        'date_active_to' => 'datetime',
    ];

    // Константы для type
    const TYPE_FIAT = 'fiat';
    const TYPE_PERCENT = 'percent';

    /**
     * Заказы использующие этот промокод
     */
    public function orders(): HasMany
    {
        return $this->hasMany(TOrders::class, 'promocode_id');
    }

    /**
     * Проверка активности промокода
     */
    public function isActive(): bool
    {
        $now = now();

        // Проверка лимита использований
        if ($this->max_usage > 0 && $this->current_usage >= $this->max_usage) {
            return false;
        }

        // Проверка даты начала
        if ($this->date_active_from && $now->lt($this->date_active_from)) {
            return false;
        }

        // Проверка даты окончания
        if ($this->date_active_to && $now->gt($this->date_active_to)) {
            return false;
        }

        return true;
    }

    /**
     * Увеличить счетчик использований
     */
    public function incrementUsage(): void
    {
        $this->increment('current_usage');
    }

    /**
     * Рассчитать скидку
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_FIAT) {
            return min($this->value, $amount);
        } else {
            return ($amount * $this->value) / 100;
        }
    }
}
