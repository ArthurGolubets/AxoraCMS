<?php

namespace HolartWeb\HolartCMS\Models\Callback;

use Illuminate\Database\Eloquent\Model;

class TUsersEmails extends Model
{
    protected $table = 't_users_emails';

    protected $fillable = [
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Possible statuses: active, unsubscribed, bounced
    const STATUS_ACTIVE = 'active';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_BOUNCED = 'bounced';
}
