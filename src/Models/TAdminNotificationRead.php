<?php

namespace HolartWeb\AxoraCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks that a given administrator has read a given TAdminNotification.
 */
class TAdminNotificationRead extends Model
{
    protected $table = 't_admin_notification_reads';

    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'administrator_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(TAdminNotification::class, 'notification_id');
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(TAdministrator::class, 'administrator_id');
    }
}
