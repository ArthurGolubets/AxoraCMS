<?php

namespace HolartWeb\AxoraCMS\Support;

use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Sync;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Connects model events to the "Битрикс24" module. Orders are the exception:
 * their items are saved after the order row, so OrderService and OrdersController
 * call Bitrix24Sync::dispatchOrder() themselves once the items exist.
 */
class Bitrix24Hooks
{
    public static function register(): void
    {
        $fire = function (string $event, \Closure $values): void {
            try {
                if (Bitrix24Sync::isActive()) {
                    app(Bitrix24Sync::class)->dispatch($event, $values());
                }
            } catch (\Throwable $e) {
                Log::warning("Bitrix24: event {$event} failed: ".$e->getMessage());
            }
        };

        Event::listen('eloquent.created: '.TUserRequests::class, function ($model) use ($fire) {
            $fire('user_request.created', fn () => MessageEvents::userRequestValues($model));
        });

        Event::listen('eloquent.created: '.TCustomFormSubmission::class, function ($model) use ($fire) {
            /** @var TCustomFormSubmission $submission */
            $submission = $model;

            // Records typed in by an administrator are not "new submissions".
            if (! $submission->administrator_id) {
                $fire('form_submission.created', fn () => MessageEvents::formSubmissionValues($submission));
            }
        });
    }
}
