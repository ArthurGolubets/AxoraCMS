<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Callback\TCustomFormSubmission;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\TAdminNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Creates admin panel notifications (bell icon). Kept as a service so every
 * order-creation path (storefront checkout via OrderService, manual creation
 * in the admin panel, future 1C/import flows, ...) can share the same call
 * without duplicating the notification shape.
 */
class AdminNotificationService
{
    public function notifyNewOrder(TOrders $order): void
    {
        if (! Schema::hasTable('t_admin_notifications')) {
            return;
        }

        $title = 'Новый заказ №'.$order->id;
        $customer = trim((string) $order->name) ?: 'без имени';
        $total = number_format((float) $order->total_price, 0, '', ' ');

        TAdminNotification::record(
            type: 'order.created',
            title: $title,
            message: "{$customer} · {$total} ₽",
            data: [
                'order_id' => $order->id,
                'total_price' => $order->total_price,
                'name' => $order->name,
            ],
            link: '/orders/'.$order->id,
        );
    }

    public function notifyNewComment(TComments $comment): void
    {
        if (! $this->canNotify()) {
            return;
        }

        $author = trim((string) $comment->name) ?: 'без имени';

        TAdminNotification::record(
            type: 'comment.created',
            title: 'Новый комментарий',
            message: $author.' · '.Str::limit((string) $comment->comment, 80),
            data: [
                'comment_id' => $comment->id,
                'product_id' => $comment->product_id,
                'rating' => $comment->rating,
            ],
            link: '/comments/'.$comment->id,
        );
    }

    public function notifyNewUserRequest(TUserRequests $userRequest): void
    {
        if (! $this->canNotify()) {
            return;
        }

        $author = trim((string) $userRequest->name) ?: 'без имени';
        $contact = $userRequest->phone ?: $userRequest->email;

        TAdminNotification::record(
            type: 'user_request.created',
            title: 'Новое обращение',
            message: trim($author.($contact ? ' · '.$contact : '')),
            data: ['user_request_id' => $userRequest->id],
            link: '/user-requests/'.$userRequest->id,
        );
    }

    public function notifyNewFormSubmission(TCustomFormSubmission $submission): void
    {
        $form = $submission->form;

        if (! $form || ! $form->notify_admin || ! Schema::hasTable('t_admin_notifications')) {
            return;
        }

        TAdminNotification::record(
            type: 'custom_form.submitted',
            title: 'Новая запись: '.$form->name,
            message: $this->submissionPreview($submission),
            data: [
                'form_id' => $form->id,
                'submission_id' => $submission->id,
            ],
            link: '/custom-forms/'.$form->id.'/submissions?open='.$submission->id,
        );
    }

    /**
     * Records created from the admin panel itself are not announced —
     * the administrator already knows about them.
     */
    protected function canNotify(): bool
    {
        if (! Schema::hasTable('t_admin_notifications')) {
            return false;
        }

        if (app()->runningInConsole()) {
            return true;
        }

        $prefix = trim((string) config('axora-cms.route_prefix', 'admin'), '/');

        return ! request()->is($prefix, $prefix.'/*');
    }

    /**
     * First few short scalar values of a submission, for the bell preview.
     */
    protected function submissionPreview(TCustomFormSubmission $submission): ?string
    {
        $parts = collect($submission->data ?? [])
            ->filter(fn ($value) => is_scalar($value) && ! is_bool($value) && trim((string) $value) !== '')
            ->take(2)
            ->map(fn ($value) => Str::limit((string) $value, 60));

        return $parts->isEmpty() ? null : $parts->implode(' · ');
    }
}
