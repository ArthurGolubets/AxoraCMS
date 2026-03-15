<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Commerce\TOrderItems;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\TelegramService;
use HolartWeb\AxoraCMS\Services\Integrations\YookassaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    /**
     * Check if commerce module is installed
     *
     * @throws \Exception
     */
    protected function checkCommerceModule(): void
    {
        if (!Schema::hasTable('t_orders')) {
            throw new \Exception('Commerce module is not installed');
        }
    }

    /**
     * Create new order
     *
     * @param array $orderData Order data (name, email, phone, etc.)
     * @param array $items Order items [['product_id' => 1, 'product_name' => 'Product', 'amount' => 2, 'total_price' => 100], ...]
     * @param int|null $userId User ID (optional)
     * @return TOrders
     * @throws \Exception
     */
    public function createOrder(array $orderData, array $items, ?int $userId = null): TOrders
    {
        $this->checkCommerceModule();

        if (empty($items)) {
            throw new \Exception('Order must contain at least one item');
        }

        return DB::transaction(function () use ($orderData, $items, $userId) {
            // Calculate totals
            $productsDataString = '';

            $goodsPrice = 0;
            foreach ($items as $item) {
                $goodsPrice += $item['total_price'] ?? 0;
            }

            $deliveryPrice = $orderData['delivery_price'] ?? 0;
            $promocodeDiscount = $orderData['promocode_discount'] ?? 0;
            $totalPrice = $goodsPrice + $deliveryPrice - $promocodeDiscount;

            // Create order
            $order = TOrders::create(array_merge($orderData, [
                'user_id' => $userId,
                'goods_price' => $goodsPrice,
                'delivery_price' => $deliveryPrice,
                'promocode_discount' => $promocodeDiscount,
                'total_price' => $totalPrice,
                'payment_status' => $orderData['payment_status'] ?? TOrders::STATUS_PENDING,
                'delivery_status' => $orderData['delivery_status'] ?? TOrders::DELIVERY_PENDING,
            ]));

            // Create order items
            foreach ($items as $item) {
                $productsDataString .= $item['product_name']." - ".$item['total_price']."руб. \n";

                TOrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'amount' => $item['amount'],
                    'total_price' => $item['total_price'],
                ]);
            }

            if(TModule::isInstalled('telegram')){
                $orderDataString = "Заказ №".$order->id."\n";
                $orderDataString .= "Имя: ".$order->name."\n";
                $orderDataString .= "Телефон: ".$order->phone."\n";
                $orderDataString .= "Адрес: ".$order->address."\n";
                $orderDataString .= "Способ оплаты: ".$order->payment_status."\n";
                $orderDataString .= "Способ доставки: ".$order->delivery_status."\n";
                $orderDataString .= "Товары: \n \n".$productsDataString;
                $message = $orderDataString;
                (new TelegramService)->sendMessage($message);
            }
            return $order->load('items');
        });
    }

    /**
     * Get orders by user ID
     *
     * @param int $userId User ID
     * @param array $filter Additional filters
     * @param array $order Ordering
     * @param int|null $perPage Items per page (null for no pagination)
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getOrdersByUser(
        int $userId,
        array $filter = [],
        array $order = ['created_at' => 'desc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        $this->checkCommerceModule();

        $query = TOrders::with('items')->where('user_id', $userId);

        $this->applyFilters($query, $filter);
        $this->applyOrdering($query, $order);

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get orders by filter
     *
     * @param array $filter Filters to apply
     * @param array $order Ordering
     * @param int|null $perPage Items per page (null for no pagination)
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getOrders(
        array $filter = [],
        array $order = ['created_at' => 'desc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        $this->checkCommerceModule();

        $query = TOrders::with('items');

        $this->applyFilters($query, $filter);
        $this->applyOrdering($query, $order);

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get order by ID
     *
     * @param int $id Order ID
     * @return TOrders|null
     * @throws \Exception
     */
    public function getOrderById(int $id): ?TOrders
    {
        $this->checkCommerceModule();

        $relations = ['items', 'promocode', 'paymentTransaction'];

        if (class_exists('App\Models\TUser')) {
            $relations[] = 'user';
        }

        return TOrders::with($relations)->find($id);
    }

    /**
     * Update order status
     *
     * @param int $id Order ID
     * @param string $status Status (payment_status or delivery_status)
     * @param string $value Status value
     * @return TOrders
     * @throws \Exception
     */
    public function updateOrderStatus(int $id, string $status, string $value): TOrders
    {
        $this->checkCommerceModule();

        $order = TOrders::findOrFail($id);

        if ($status === 'payment_status') {
            $allowedStatuses = [
                TOrders::STATUS_PENDING,
                TOrders::STATUS_PAID,
                TOrders::STATUS_FAILED,
                TOrders::STATUS_REFUNDED,
            ];

            if (!in_array($value, $allowedStatuses)) {
                throw new \Exception("Invalid payment status: {$value}");
            }

            $order->payment_status = $value;
        } elseif ($status === 'delivery_status') {
            $allowedStatuses = [
                TOrders::DELIVERY_PENDING,
                TOrders::DELIVERY_PROCESSING,
                TOrders::DELIVERY_SHIPPED,
                TOrders::DELIVERY_DELIVERED,
                TOrders::DELIVERY_CANCELLED,
            ];

            if (!in_array($value, $allowedStatuses)) {
                throw new \Exception("Invalid delivery status: {$value}");
            }

            $order->delivery_status = $value;
        } else {
            throw new \Exception("Invalid status type: {$status}");
        }

        $order->save();

        return $order->fresh();
    }

    /**
     * Update order payment status
     *
     * @param int $id Order ID
     * @param string $status Payment status
     * @return TOrders
     * @throws \Exception
     */
    public function updatePaymentStatus(int $id, string $status): TOrders
    {
        return $this->updateOrderStatus($id, 'payment_status', $status);
    }

    /**
     * Update order delivery status
     *
     * @param int $id Order ID
     * @param string $status Delivery status
     * @return TOrders
     * @throws \Exception
     */
    public function updateDeliveryStatus(int $id, string $status): TOrders
    {
        return $this->updateOrderStatus($id, 'delivery_status', $status);
    }

    /**
     * Cancel order
     *
     * @param int $id Order ID
     * @return TOrders
     * @throws \Exception
     */
    public function cancelOrder(int $id): TOrders
    {
        return $this->updateDeliveryStatus($id, TOrders::DELIVERY_CANCELLED);
    }

    /**
     * Get orders count by filter
     *
     * @param array $filter Filters to apply
     * @return int
     * @throws \Exception
     */
    public function countOrders(array $filter = []): int
    {
        $this->checkCommerceModule();

        $query = TOrders::query();

        $this->applyFilters($query, $filter);

        return $query->count();
    }

    /**
     * Get orders by status
     *
     * @param string $statusType 'payment_status' or 'delivery_status'
     * @param string $status Status value
     * @param array $order Ordering
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getOrdersByStatus(
        string $statusType,
        string $status,
        array $order = ['created_at' => 'desc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        if (!in_array($statusType, ['payment_status', 'delivery_status'])) {
            throw new \Exception("Invalid status type: {$statusType}");
        }

        return $this->getOrders([$statusType => $status], $order, $perPage, $page);
    }

    /**
     * Get pending orders
     *
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getPendingOrders(?int $perPage = null, int $page = 1)
    {
        return $this->getOrdersByStatus('delivery_status', TOrders::DELIVERY_PENDING, ['created_at' => 'desc'], $perPage, $page);
    }

    /**
     * Get paid orders
     *
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getPaidOrders(?int $perPage = null, int $page = 1)
    {
        return $this->getOrdersByStatus('payment_status', TOrders::STATUS_PAID, ['created_at' => 'desc'], $perPage, $page);
    }

    /**
     * Calculate order total
     *
     * @param array $items Order items
     * @param float $deliveryPrice Delivery price
     * @param float $promocodeDiscount Promocode discount
     * @return array ['goods_price', 'total_price']
     */
    public function calculateOrderTotal(array $items, float $deliveryPrice = 0, float $promocodeDiscount = 0): array
    {
        $goodsPrice = 0;

        foreach ($items as $item) {
            $goodsPrice += $item['total_price'] ?? 0;
        }

        $totalPrice = $goodsPrice + $deliveryPrice - $promocodeDiscount;

        return [
            'goods_price' => $goodsPrice,
            'delivery_price' => $deliveryPrice,
            'promocode_discount' => $promocodeDiscount,
            'total_price' => max(0, $totalPrice), // Ensure total is not negative
        ];
    }

    /**
     * Delete order
     *
     * @param int $id Order ID
     * @return bool
     * @throws \Exception
     */
    public function deleteOrder(int $id): bool
    {
        $this->checkCommerceModule();

        $order = TOrders::find($id);

        if (!$order) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            // Delete order items
            $order->items()->delete();

            // Delete order
            return $order->delete();
        });
    }

    /**
     * Update order data
     *
     * @param int $id Order ID
     * @param array $data Data to update
     * @return TOrders
     * @throws \Exception
     */
    public function updateOrder(int $id, array $data): TOrders
    {
        $this->checkCommerceModule();

        $order = TOrders::findOrFail($id);

        $order->update($data);

        return $order->fresh(['items']);
    }

    /**
     * Get order statistics
     *
     * @param array $filter Optional filters
     * @return array
     * @throws \Exception
     */
    public function getOrderStatistics(array $filter = []): array
    {
        $this->checkCommerceModule();

        $query = TOrders::query();

        $this->applyFilters($query, $filter);

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_price'),
            'average_order_value' => $query->avg('total_price'),
            'pending_orders' => (clone $query)->where('delivery_status', TOrders::DELIVERY_PENDING)->count(),
            'processing_orders' => (clone $query)->where('delivery_status', TOrders::DELIVERY_PROCESSING)->count(),
            'shipped_orders' => (clone $query)->where('delivery_status', TOrders::DELIVERY_SHIPPED)->count(),
            'delivered_orders' => (clone $query)->where('delivery_status', TOrders::DELIVERY_DELIVERED)->count(),
            'cancelled_orders' => (clone $query)->where('delivery_status', TOrders::DELIVERY_CANCELLED)->count(),
            'paid_orders' => (clone $query)->where('payment_status', TOrders::STATUS_PAID)->count(),
            'unpaid_orders' => (clone $query)->where('payment_status', TOrders::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Apply filters to query
     *
     * @param $query
     * @param array $filter
     * @return void
     */
    protected function applyFilters($query, array $filter): void
    {
        foreach ($filter as $key => $value) {
            if (in_array($key, [
                'id', 'user_id', 'payment_status', 'delivery_status',
                'payment_type', 'delivery_type', 'promocode_id'
            ])) {
                $query->where($key, $value);
            } elseif (in_array($key, ['name', 'email', 'phone'])) {
                $query->where($key, 'like', "%{$value}%");
            } elseif ($key === 'date_from') {
                $query->where('created_at', '>=', $value);
            } elseif ($key === 'date_to') {
                $query->where('created_at', '<=', $value);
            } elseif ($key === 'total_price_min') {
                $query->where('total_price', '>=', $value);
            } elseif ($key === 'total_price_max') {
                $query->where('total_price', '<=', $value);
            }
        }
    }

    /**
     * Apply ordering to query
     *
     * @param $query
     * @param array $order
     * @return void
     */
    protected function applyOrdering($query, array $order): void
    {
        foreach ($order as $field => $direction) {
            $direction = strtolower($direction);

            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }

            if (in_array($field, [
                'id', 'created_at', 'updated_at', 'total_price',
                'goods_price', 'delivery_price', 'payment_status', 'delivery_status'
            ])) {
                $query->orderBy($field, $direction);
            }
        }
    }

    /**
     * Generate payment link for order (only if Yookassa module is configured)
     *
     * @param int $orderId Order ID
     * @return string|null Payment link or null if Yookassa is not configured
     * @throws \Exception
     */
    public function generatePaymentLink(int $orderId, string $routeName = 'order.success'): ?string
    {
        $this->checkCommerceModule();

        $yookassaService = new YookassaService();

        if (!$yookassaService->isConfigured()) {
            throw new \Exception('Yookassa module is not configured');
        }

        $order = $this->getOrderById($orderId);

        if (!$order) {
            throw new \Exception("Order not found: {$orderId}");
        }

        // Prepare items for payment
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->product_name,
                'price' => (float) $item->total_price / $item->amount,
                'quantity' => $item->amount,
            ];
        }

        // Prepare payment data
        $paymentData = [
            'order_id' => $order->id,
            'amount' => (float) $order->total_price,
            'currency' => 'RUB',
            'description' => "Оплата заказа №{$order->id}",
            'email' => $order->email,
            'phone' => $order->phone,
            'items' => $items,
        ];

        return $yookassaService->createOrderPayment($paymentData,$routeName);
    }
}
