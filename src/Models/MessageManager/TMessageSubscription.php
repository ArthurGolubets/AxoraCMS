<?php

namespace HolartWeb\AxoraCMS\Models\MessageManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Подписка на событие": what the system does when an event is triggered.
 */
class TMessageSubscription extends Model
{
    protected $table = 't_message_subscriptions';

    protected $fillable = [
        'name',
        'event',
        'is_active',
        'conditions',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'conditions' => 'array',
        'sort' => 'integer',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(TMessageAction::class, 'subscription_id')->orderBy('sort')->orderBy('id');
    }
}
