<?php

namespace HolartWeb\AxoraCMS\Models\MessageManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a subscription: a message sent through a channel.
 */
class TMessageAction extends Model
{
    public const CHANNEL_TELEGRAM = 'telegram';

    public const CHANNEL_EMAIL = 'email';

    protected $table = 't_message_actions';

    protected $fillable = [
        'subscription_id',
        'channel',
        'is_active',
        'sort',
        'delay_seconds',
        'config',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'delay_seconds' => 'integer',
        'config' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TMessageSubscription::class, 'subscription_id');
    }
}
