<?php

namespace HolartWeb\HolartCMS\Models\Callback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TComments extends Model
{
    protected $table = 't_comments';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'comment',
        'rating',
        'product_id',
        'is_moderated',
    ];

    protected $casts = [
        'rating' => 'integer',
        'product_id' => 'integer',
        'is_moderated' => 'boolean',
    ];

    /**
     * Get the product associated with the comment
     */
    public function product(): BelongsTo
    {
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            return $this->belongsTo(\HolartWeb\HolartCMS\Models\Shop\TProduct::class, 'product_id');
        }
        return $this->belongsTo(Model::class, 'product_id');
    }
}
