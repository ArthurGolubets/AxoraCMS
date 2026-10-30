<?php

namespace HolartWeb\AxoraCMS\Models\Integrations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TBitrix24Log extends Model
{
    public const STATUS_SUCCESS = 'success';

    /** The main record exists, but a later step (product rows) failed. */
    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $table = 't_bitrix24_logs';

    protected $fillable = [
        'rule_id',
        'rule_name',
        'event',
        'status',
        'entity',
        'entity_id',
        'contact_id',
        'company_id',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TBitrix24Rule::class, 'rule_id');
    }
}
