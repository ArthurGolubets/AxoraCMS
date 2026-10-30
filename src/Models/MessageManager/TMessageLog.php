<?php

namespace HolartWeb\AxoraCMS\Models\MessageManager;

use Illuminate\Database\Eloquent\Model;

/**
 * Delivery journal: what was sent, to whom and with which result.
 */
class TMessageLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public $timestamps = false;

    protected $table = 't_message_logs';

    protected $fillable = [
        'subscription_id',
        'action_id',
        'event',
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'error',
        'is_test',
        'created_at',
    ];

    protected $casts = [
        'subscription_id' => 'integer',
        'action_id' => 'integer',
        'is_test' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }
}
