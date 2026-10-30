<?php

namespace HolartWeb\AxoraCMS\Models\MessageManager;

use Illuminate\Database\Eloquent\Model;

/**
 * Email template of the "Менеджер сообщений" module: subject + HTML body with #VARIABLES#.
 */
class TMessageTemplate extends Model
{
    protected $table = 't_message_templates';

    protected $fillable = [
        'name',
        'event',
        'subject',
        'body',
        'blocks',
        'is_active',
    ];

    protected $casts = [
        'blocks' => 'array',
        'is_active' => 'boolean',
    ];
}
