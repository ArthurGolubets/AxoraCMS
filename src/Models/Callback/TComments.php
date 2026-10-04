<?php

namespace HolartWeb\AxoraCMS\Models\Callback;

use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

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
     * Every creation path (storefront services, custom controllers) goes through
     * Eloquent, so the admin notification is hooked here.
     */
    protected static function booted(): void
    {
        static::created(function (self $record) {
            try {
                app(AdminNotificationService::class)->notifyNewComment($record);
            } catch (\Throwable $e) {
                Log::warning('Failed to create admin notification: '.$e->getMessage());
            }
        });
    }

    /**
     * Get the product associated with the comment
     */
    public function product(): BelongsTo
    {
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            return $this->belongsTo(TProduct::class, 'product_id');
        }

        return $this->belongsTo(Model::class, 'product_id');
    }
}
