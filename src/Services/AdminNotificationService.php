<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\TAdminNotification;
use Illuminate\Support\Facades\Schema;

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
}
