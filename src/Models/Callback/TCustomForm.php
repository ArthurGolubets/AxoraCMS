<?php

namespace HolartWeb\AxoraCMS\Models\Callback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-defined form ("Своя форма") — its fields and the submissions it collected.
 */
class TCustomForm extends Model
{
    protected $table = 't_custom_forms';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'admin_can_create',
        'notify_admin',
        'success_message',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'admin_can_create' => 'boolean',
        'notify_admin' => 'boolean',
        'sort' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(TCustomFormField::class, 'form_id')->orderBy('sort')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TCustomFormSubmission::class, 'form_id');
    }
}
