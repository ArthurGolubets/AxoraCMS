<?php

namespace HolartWeb\AxoraCMS\Console;

use HolartWeb\AxoraCMS\Jobs\RefreshCdekShipmentJob;
use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Console\Command;

/**
 * Queues a status check of every shipment still on its way.
 */
class CdekSyncCommand extends Command
{
    /** CDEK allows only so many requests at once, so the checks are spread out. */
    private const SECONDS_BETWEEN_CHECKS = 2;

    protected $signature = 'axoracms:cdek-sync';

    protected $description = 'Refresh track numbers and statuses of CDEK shipments';

    public function handle(): int
    {
        if (! CdekShipments::isActive()) {
            return self::SUCCESS;
        }

        $delay = 0;

        TCdekShipment::whereIn('state', [TCdekShipment::STATE_ACCEPTED, TCdekShipment::STATE_ACTIVE])
            ->select('id')
            ->chunkById(200, function ($shipments) use (&$delay) {
                foreach ($shipments as $shipment) {
                    // Attempt = max: this round must not start its own chain of retries.
                    RefreshCdekShipmentJob::dispatch($shipment->id, RefreshCdekShipmentJob::MAX_ATTEMPTS)
                        ->delay(now()->addSeconds($delay));
                    $delay += self::SECONDS_BETWEEN_CHECKS;
                }
            });

        return self::SUCCESS;
    }
}
