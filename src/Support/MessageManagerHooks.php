<?php

namespace HolartWeb\AxoraCMS\Support;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageEvents;
use HolartWeb\AxoraCMS\Services\MessageManager\MessageManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Connects model events to the "Менеджер сообщений" module.
 *
 * Every record is created through Eloquent, so listening to model events covers
 * the storefront, the admin panel and imports alike. Orders are the exception for
 * "created": their items are saved after the order row, so OrderService and
 * OrdersController trigger that event themselves once the items exist.
 */
class MessageManagerHooks
{
    public static function register(): void
    {
        // Values are built lazily and under try/catch: a broken listener must never block saving a record.
        $fire = function (string $event, \Closure $values): void {
            try {
                if (MessageManager::isActive()) {
                    app(MessageManager::class)->dispatch($event, $values());
                }
            } catch (\Throwable $e) {
                Log::warning("MessageManager: event {$event} failed: ".$e->getMessage());
            }
        };
        $manager = fn (): MessageManager => app(MessageManager::class);

        Event::listen('eloquent.creating: '.TComments::class, function ($model) use ($fire) {
            $fire('comment.creating', fn () => MessageEvents::commentValues($model));
        });
        Event::listen('eloquent.created: '.TComments::class, function ($model) use ($fire) {
            $fire('comment.created', fn () => MessageEvents::commentValues($model));
        });
        Event::listen('eloquent.updated: '.TComments::class, function ($model) use ($fire) {
            /** @var TComments $comment */
            $comment = $model;

            if ($comment->wasChanged('is_moderated') && $comment->is_moderated) {
                $fire('comment.moderated', fn () => MessageEvents::commentValues($comment));
            }
        });

        Event::listen('eloquent.creating: '.TUserRequests::class, function ($model) use ($fire) {
            $fire('user_request.creating', fn () => MessageEvents::userRequestValues($model));
        });
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

        Event::listen('eloquent.updated: '.TOrders::class, function ($model) use ($manager) {
            /** @var TOrders $order */
            $order = $model;

            if (! $order->wasChanged(['payment_status', 'delivery_status'])) {
                return;
            }

            if ($order->wasChanged('payment_status')) {
                $manager()->dispatchOrder('order.payment_status_changed', $order, [
                    'OLD_STATUS' => MessageEvents::paymentStatusLabel($order->getOriginal('payment_status')),
                    'NEW_STATUS' => MessageEvents::paymentStatusLabel($order->payment_status),
                ]);
            }

            if ($order->wasChanged('delivery_status')) {
                $manager()->dispatchOrder('order.delivery_status_changed', $order, [
                    'OLD_STATUS' => MessageEvents::deliveryStatusLabel($order->getOriginal('delivery_status')),
                    'NEW_STATUS' => MessageEvents::deliveryStatusLabel($order->delivery_status),
                ]);
            }
        });
    }
}
