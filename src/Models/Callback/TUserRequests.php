<?php

namespace HolartWeb\HolartCMS\Models\Callback;

use Illuminate\Database\Eloquent\Model;

class TUserRequests extends Model
{
    protected $table = 't_user_requests';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'comment',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];
}
