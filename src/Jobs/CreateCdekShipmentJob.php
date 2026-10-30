<?php

namespace HolartWeb\AxoraCMS\Jobs;

use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends an order to CDEK in the background, so neither the customer nor the
 * manager waits for the carrier.
 */
class CreateCdekShipmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    public function __construct(public int $shipmentId) {}

    public function handle(CdekShipments $shipments): void
    {
        $shipment = TCdekShipment::find($this->shipmentId);

        if ($shipment) {
            $shipments->send($shipment);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $shipment = TCdekShipment::find($this->shipmentId);

        if ($shipment) {
            app(CdekShipments::class)->fail($shipment, $exception?->getMessage() ?: 'Не удалось передать заказ в СДЭК');
        }
    }
}
