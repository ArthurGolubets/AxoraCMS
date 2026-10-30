<?php

namespace HolartWeb\AxoraCMS\Models\Integrations;

use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TCdekShipment extends Model
{
    /** Waiting in the queue to be sent to CDEK. */
    public const STATE_QUEUED = 'queued';

    /** Sent, CDEK has not given the track number yet. */
    public const STATE_ACCEPTED = 'accepted';

    /** Has a track number and is on its way. */
    public const STATE_ACTIVE = 'active';

    /** Delivered or returned, nothing more will change. */
    public const STATE_FINISHED = 'finished';

    public const STATE_ERROR = 'error';

    protected $table = 't_cdek_shipments';

    protected $fillable = [
        'order_id',
        'state',
        'uuid',
        'track_number',
        'cdek_status',
        'cdek_status_name',
        'cdek_status_at',
        'delivery_point',
        'city_code',
        'address',
        'weight',
        'length',
        'width',
        'height',
        'error',
    ];

    protected $casts = [
        'cdek_status_at' => 'datetime',
        'city_code' => 'integer',
        'weight' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TOrders::class, 'order_id');
    }

    public function trackingUrl(): ?string
    {
        return $this->track_number ? 'https://www.cdek.ru/ru/tracking?order_id='.$this->track_number : null;
    }
}
