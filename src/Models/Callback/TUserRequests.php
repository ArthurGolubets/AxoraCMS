<?php

namespace HolartWeb\AxoraCMS\Models\Callback;

use HolartWeb\AxoraCMS\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TUserRequests extends Model
{
    protected $table = 't_user_requests';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'comment',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'viewed_at' => 'datetime',
    ];

    /**
     * Every creation path (storefront services, custom controllers) goes through
     * Eloquent, so the admin notification is hooked here.
     */
    protected static function booted(): void
    {
        static::created(function (self $record) {
            try {
                app(AdminNotificationService::class)->notifyNewUserRequest($record);
            } catch (\Throwable $e) {
                Log::warning('Failed to create admin notification: '.$e->getMessage());
            }
        });
    }
}
