<?php

namespace HolartWeb\AxoraCMS\Models\Callback;

use HolartWeb\AxoraCMS\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * A single filled-in custom form ("запись формы").
 */
class TCustomFormSubmission extends Model
{
    protected $table = 't_custom_form_submissions';

    protected $fillable = [
        'form_id',
        'data',
        'user_id',
        'administrator_id',
        'ip',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'user_id' => 'integer',
        'administrator_id' => 'integer',
        'viewed_at' => 'datetime',
    ];

    /**
     * Notify administrators about submissions coming from the site
     * (records created by an administrator are not announced).
     */
    protected static function booted(): void
    {
        static::created(function (self $submission) {
            if ($submission->administrator_id) {
                return;
            }

            try {
                app(AdminNotificationService::class)->notifyNewFormSubmission($submission);
            } catch (\Throwable $e) {
                Log::warning('Failed to create form submission admin notification: '.$e->getMessage());
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(TCustomForm::class, 'form_id');
    }

    /**
     * Value of a field by its code.
     */
    public function value(string $code, mixed $default = null): mixed
    {
        return $this->data[$code] ?? $default;
    }
}
