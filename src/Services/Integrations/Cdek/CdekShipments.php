<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Cdek;

use HolartWeb\AxoraCMS\Jobs\CreateCdekShipmentJob;
use HolartWeb\AxoraCMS\Jobs\RefreshCdekShipmentJob;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Commerce\TOrdersData;
use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\TModule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Heart of the "СДЭК" module. Talking to CDEK is slow, so it only ever happens in
 * jobs: the order is sent by CreateCdekShipmentJob, the track number (CDEK issues it
 * asynchronously) and later statuses are picked up by RefreshCdekShipmentJob.
 */
class CdekShipments
{
    public const MODULE_NAME = 'cdek';

    public const ENABLED_SETTING = 'delivery_cdek_enabled';

    public const DEFAULTS = [
        'client_id' => '',
        'test_mode' => false,
        'auto_create' => false,
        'cash_on_delivery' => false,
        'tariff_pvz' => 136,
        'tariff_door' => 137,
        'shipment_point' => '',
        'from_city_code' => null,
        'from_address' => '',
        'default_weight' => 500,
        'default_length' => 20,
        'default_width' => 20,
        'default_height' => 10,
    ];

    /** Statuses after which a shipment no longer changes. */
    private const FINAL_STATUSES = ['DELIVERED', 'NOT_DELIVERED', 'INVALID', 'CANCELLED'];

    public function __construct(private CdekClient $client) {}

    public static function isActive(): bool
    {
        return TModule::isInstalled(self::MODULE_NAME);
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return array_intersect_key(TIntegrationSettings::getAll(CdekClient::INTEGRATION), self::DEFAULTS) + self::DEFAULTS;
    }

    /**
     * Send an order placed with CDEK delivery, when the settings ask for that.
     * Nothing here may break the checkout.
     */
    public function handleNewOrder(TOrders $order): void
    {
        try {
            if (
                self::isActive()
                && $order->delivery_type === TOrders::DELIVERY_CDEK
                && TOrdersData::getValue(self::ENABLED_SETTING, false)
                && self::settings()['auto_create']
            ) {
                $data = (array) $order->addition_data;

                $this->schedule($order, [
                    'delivery_point' => $data['cdek_pvz'] ?? null,
                    'city_code' => $data['cdek_city_code'] ?? null,
                    'address' => $order->delivery_address,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("CDEK: order {$order->id} was not queued: ".$e->getMessage());
        }
    }

    /**
     * Queue sending the order. A failed attempt is replaced, a live shipment is kept.
     *
     * @param  array<string, mixed>  $details  delivery_point, city_code, address, weight, length, width, height
     */
    public function schedule(TOrders $order, array $details = []): TCdekShipment
    {
        $shipment = TCdekShipment::firstOrNew(['order_id' => $order->id]);

        if ($shipment->exists && $shipment->state !== TCdekShipment::STATE_ERROR) {
            throw new CdekException('Заказ уже передан в СДЭК');
        }

        $shipment->fill(['state' => TCdekShipment::STATE_QUEUED, 'uuid' => null, 'error' => null] + $details)->save();

        CreateCdekShipmentJob::dispatch($shipment->id);

        return $shipment;
    }

    /**
     * Send the order to CDEK. Only a network or server failure is thrown, so the
     * job can try again; a rejected order will not get better by itself.
     *
     * @throws CdekException
     */
    public function send(TCdekShipment $shipment): void
    {
        if ($shipment->state !== TCdekShipment::STATE_QUEUED || $shipment->uuid) {
            return;
        }

        try {
            $response = $this->client->post('orders', $this->buildPayload($shipment));
            $uuid = $response['entity']['uuid'] ?? null;

            if (! $uuid) {
                throw new CdekException('СДЭК не вернул идентификатор заказа');
            }
        } catch (CdekException $e) {
            if ($e->retryable) {
                throw $e;
            }

            $this->fail($shipment, $e->getMessage());

            return;
        }

        $shipment->update(['uuid' => $uuid, 'state' => TCdekShipment::STATE_ACCEPTED]);

        RefreshCdekShipmentJob::dispatch($shipment->id)->delay(now()->addSeconds(RefreshCdekShipmentJob::RETRY_SECONDS));
    }

    /**
     * Read the track number and the status from CDEK, and move the order along.
     *
     * @throws CdekException
     */
    public function refresh(TCdekShipment $shipment): void
    {
        if (! $shipment->uuid) {
            return;
        }

        $response = $this->client->get('orders/'.$shipment->uuid);
        $rejected = collect($response['requests'] ?? [])->firstWhere('state', 'INVALID');

        if ($rejected) {
            $this->fail($shipment, implode('; ', array_column($rejected['errors'] ?? [], 'message')) ?: 'СДЭК отклонил заказ');

            return;
        }

        $entity = $response['entity'] ?? [];
        $status = collect($entity['statuses'] ?? [])->sortByDesc('date_time')->first();
        $number = $entity['cdek_number'] ?? null;
        $code = $status['code'] ?? null;

        $shipment->fill([
            'track_number' => $number ?: $shipment->track_number,
            'cdek_status' => $code ?? $shipment->cdek_status,
            'cdek_status_name' => $status['name'] ?? $shipment->cdek_status_name,
            'cdek_status_at' => isset($status['date_time']) ? Carbon::parse($status['date_time']) : $shipment->cdek_status_at,
        ]);

        if (in_array($code, self::FINAL_STATUSES, true)) {
            $shipment->state = TCdekShipment::STATE_FINISHED;
        } elseif ($shipment->track_number) {
            $shipment->state = TCdekShipment::STATE_ACTIVE;
        }

        $shipment->save();

        if ($code) {
            $this->syncOrderStatus($shipment, $code);
        }
    }

    public function fail(TCdekShipment $shipment, string $message): void
    {
        $shipment->update(['state' => TCdekShipment::STATE_ERROR, 'error' => $message]);
    }

    /**
     * Goes through the model, so the "Менеджер сообщений" rules fire as usual.
     */
    private function syncOrderStatus(TCdekShipment $shipment, string $code): void
    {
        $status = match ($code) {
            'DELIVERED' => TOrders::DELIVERY_DELIVERED,
            'NOT_DELIVERED', 'CANCELLED' => TOrders::DELIVERY_CANCELLED,
            'CREATED', 'ACCEPTED' => TOrders::DELIVERY_PROCESSING,
            default => TOrders::DELIVERY_SHIPPED,
        };

        $order = $shipment->order;

        if ($order && $order->delivery_status !== $status) {
            $order->update(['delivery_status' => $status]);
        }
    }

    /**
     * Body of POST /orders, type 1: an online store selling to a customer.
     *
     * @return array<string, mixed>
     *
     * @throws CdekException
     */
    public function buildPayload(TCdekShipment $shipment): array
    {
        $order = $shipment->order;
        $settings = self::settings();

        if (! $order) {
            throw new CdekException('Заказ не найден');
        }

        if (! $order->phone) {
            throw new CdekException('В заказе не указан телефон получателя');
        }

        if (! $shipment->delivery_point && ! $shipment->address) {
            throw new CdekException('Укажите пункт выдачи или адрес доставки');
        }

        $payload = [
            'type' => 1,
            'number' => (string) $order->id,
            'tariff_code' => (int) ($shipment->delivery_point ? $settings['tariff_pvz'] : $settings['tariff_door']),
            'recipient' => array_filter([
                'name' => (string) $order->name,
                'email' => $order->email,
                'phones' => [['number' => (string) $order->phone]],
            ]),
            'packages' => [$this->package($shipment, $order, $settings)],
        ];

        if ($settings['shipment_point']) {
            $payload['shipment_point'] = $settings['shipment_point'];
        } else {
            $payload['from_location'] = array_filter(['code' => $settings['from_city_code'], 'address' => $settings['from_address']]);
        }

        if ($shipment->delivery_point) {
            $payload['delivery_point'] = $shipment->delivery_point;
        } else {
            $payload['to_location'] = array_filter(['code' => $shipment->city_code, 'address' => $shipment->address]);
        }

        if ($settings['cash_on_delivery'] && $order->payment_status !== TOrders::STATUS_PAID && (float) $order->delivery_price > 0) {
            $payload['delivery_recipient_cost'] = ['value' => (float) $order->delivery_price];
        }

        return $payload;
    }

    /**
     * Everything goes as one package. The discount is spread over the items, so the
     * sum to collect on delivery matches what the customer agreed to pay.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function package(TCdekShipment $shipment, TOrders $order, array $settings): array
    {
        $items = $order->items()->get();
        $units = max(1, (int) $items->sum('amount'));
        $weight = (int) ($shipment->weight ?: $settings['default_weight'] * $units);
        $goods = (float) $order->goods_price;
        $discountFactor = $goods > 0 ? max(0, $goods - (float) $order->promocode_discount) / $goods : 1;
        $collect = $settings['cash_on_delivery'] && $order->payment_status !== TOrders::STATUS_PAID;

        return [
            'number' => '1',
            'weight' => $weight,
            'length' => (int) ($shipment->length ?: $settings['default_length']),
            'width' => (int) ($shipment->width ?: $settings['default_width']),
            'height' => (int) ($shipment->height ?: $settings['default_height']),
            'items' => $items->map(function ($item) use ($weight, $units, $discountFactor, $collect) {
                $amount = max(1, (int) $item->amount);
                $price = round((float) $item->total_price / $amount * $discountFactor, 2);

                return [
                    'name' => (string) $item->product_name,
                    'ware_key' => (string) ($item->product_id ?: $item->id),
                    'payment' => ['value' => $collect ? $price : 0],
                    'cost' => $price,
                    'weight' => max(1, (int) floor($weight / $units)),
                    'amount' => $amount,
                ];
            })->values()->all(),
        ];
    }
}
