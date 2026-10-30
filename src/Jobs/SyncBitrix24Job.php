<?php

namespace HolartWeb\AxoraCMS\Jobs;

use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Rule;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Sync;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Runs one rule right after the response, which needs no queue worker. The values
 * are captured when the event happens.
 */
class SyncBitrix24Job
{
    use Dispatchable;

    /**
     * @param  array<string, string>  $values
     * @param  array<int, array{name: string, price: float, quantity: float}>  $products
     */
    public function __construct(
        public int $ruleId,
        public string $event,
        public array $values,
        public array $products = [],
    ) {}

    public function handle(Bitrix24Sync $sync): void
    {
        $rule = TBitrix24Rule::find($this->ruleId);

        // Deleted or switched off in the meantime.
        if ($rule && $rule->is_active) {
            $sync->run($rule, $this->event, $this->values, $this->products);
        }
    }
}
