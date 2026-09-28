<?php

namespace HolartWeb\AxoraCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A notification shown in the admin panel bell icon (shared across every
 * administrator). Per-admin read state lives in TAdminNotificationRead.
 */
class TAdminNotification extends Model
{
    protected $table = 't_admin_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'link',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(TAdminNotificationRead::class, 'notification_id');
    }

    /**
     * Create a notification. Kept as a single choke point so every event
     * type gets the same shape.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function record(string $type, string $title, ?string $message = null, ?array $data = null, ?string $link = null): self
    {
        return static::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
        ]);
    }
}
