<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport\Exporters;

use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\TModule;

/**
 * Orders with their items: one row per item (order columns repeat).
 *
 * Options: date_from, date_to, delivery_status, payment_status.
 */
class OrdersExport extends AbstractExportSource
{
    protected const DELIVERY_TYPES = ['pickup' => 'Самовывоз', 'courier' => 'Курьер', 'post' => 'Почта'];

    protected const PAYMENT_TYPES = ['online' => 'Онлайн', 'cash' => 'Наличные', 'card' => 'Картой'];

    protected const PAYMENT_STATUSES = ['pending' => 'Ожидает оплаты', 'paid' => 'Оплачен', 'failed' => 'Ошибка оплаты', 'refunded' => 'Возврат'];

    protected const DELIVERY_STATUSES = ['pending' => 'Новый', 'processing' => 'В обработке', 'shipped' => 'Отправлен', 'delivered' => 'Доставлен', 'cancelled' => 'Отменён'];

    public function key(): string
    {
        return 'orders';
    }

    public function label(): string
    {
        return 'Заказы';
    }

    public function available(): bool
    {
        return TModule::isInstalled('commerce');
    }

    public function title(array $options): string
    {
        return 'Экспорт заказов';
    }

    public function headers(array $options): array
    {
        return ['№ заказа', 'Дата', 'Статус', 'Оплата', 'Покупатель', 'Телефон', 'Email', 'Доставка', 'Адрес',
            'Способ оплаты', 'Сумма товаров', 'Стоимость доставки', 'Скидка по промокоду', 'Итого', 'Комментарий',
            'Товар', 'ID товара', 'Вариант', 'Количество', 'Сумма позиции'];
    }

    public function total(array $options): int
    {
        return $this->query($options)->count();
    }

    public function rows(array $options, int $offset, int $limit): array
    {
        $rows = [];

        $orders = $this->query($options)->with('items')->orderBy('id')->skip($offset)->take($limit)->get();
        foreach ($orders as $order) {
            $orderColumns = [
                $order->id,
                $this->date($order->created_at),
                self::DELIVERY_STATUSES[$order->delivery_status] ?? $order->delivery_status,
                self::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status,
                $order->name,
                $order->phone,
                $order->email,
                self::DELIVERY_TYPES[$order->delivery_type] ?? $order->delivery_type,
                $order->delivery_address,
                self::PAYMENT_TYPES[$order->payment_type] ?? $order->payment_type,
                (float) $order->goods_price,
                (float) $order->delivery_price,
                (float) $order->promocode_discount,
                (float) $order->total_price,
                $order->comments,
            ];

            if ($order->items->isEmpty()) {
                $rows[] = array_merge($orderColumns, ['', '', '', '', '']);

                continue;
            }

            foreach ($order->items as $item) {
                $variant = is_array($item->variant_data) ? ($item->variant_data['name'] ?? '') : '';
                $rows[] = array_merge($orderColumns, [
                    $item->product_name,
                    $item->product_id,
                    $variant,
                    $item->amount,
                    (float) $item->total_price,
                ]);
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function query(array $options)
    {
        $query = TOrders::query()
            ->when(! empty($options['delivery_status']), fn ($q) => $q->where('delivery_status', $options['delivery_status']))
            ->when(! empty($options['payment_status']), fn ($q) => $q->where('payment_status', $options['payment_status']));

        return $this->applyDateRange($query, $options);
    }
}
