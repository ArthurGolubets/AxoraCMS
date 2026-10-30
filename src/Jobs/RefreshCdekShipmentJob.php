<?php

namespace HolartWeb\AxoraCMS\Jobs;

use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekException;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Picks up the track number and the status. CDEK registers an order asynchronously,
 * so while the number is missing the job comes back a few times by itself;
 * after that axoracms:cdek-sync keeps checking.
 */
class RefreshCdekShipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public const RETRY_SECONDS = 30;

    public const MAX_ATTEMPTS = 10;

    public function __construct(public int $shipmentId, public int $attempt = 1) {}

    public function handle(CdekShipments $shipments): void
    {
        $shipment = TCdekShipment::find($this->shipmentId);

        if (! $shipment) {
            return;
        }

        try {
            $shipments->refresh($shipment);
        } catch (CdekException $e) {
            Log::warning("CDEK: shipment {$shipment->id} was not refreshed: ".$e->getMessage());
        }

        if ($shipment->state === TCdekShipment::STATE_ACCEPTED && $this->attempt < self::MAX_ATTEMPTS) {
            self::dispatch($shipment->id, $this->attempt + 1)->delay(now()->addSeconds(self::RETRY_SECONDS));
        }
    }
}
