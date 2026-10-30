<?php

namespace HolartWeb\AxoraCMS\Jobs;

use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends one step of a subscription. The variable values are captured when the
 * event happens, so a delayed message still describes the moment it was triggered.
 */
class SendMessageActionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /**
     * @param  array<string, string>  $values
     * @param  array<string, string>  $html
     */
    public function __construct(
        public int $actionId,
        public string $event,
        public array $values,
        public array $html = [],
    ) {}

    public function handle(MessageManager $manager): void
    {
        $action = TMessageAction::with('subscription')->find($this->actionId);

        // Deleted or switched off while the job was waiting in the queue.
        if (! $action || ! $action->is_active || ! $action->subscription?->is_active) {
            return;
        }

        $manager->runAction($action, $this->event, $this->values, $this->html);
    }
}
