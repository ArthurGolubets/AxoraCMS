<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integrations;

use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekClient;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekException;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CdekController extends Controller
{
    /**
     * Saved settings. The secret never leaves the server.
     */
    public function index(): JsonResponse
    {
        return response()->json(CdekShipments::settings() + ['has_secret' => (bool) CdekClient::storedSecret()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'test_mode' => 'boolean',
            'auto_create' => 'boolean',
            'cash_on_delivery' => 'boolean',
            'tariff_pvz' => 'required|integer|min:1',
            'tariff_door' => 'required|integer|min:1',
            'shipment_point' => 'nullable|string|max:32',
            'from_city_code' => 'nullable|integer|min:1',
            'from_address' => 'nullable|string|max:255',
            'default_weight' => 'required|integer|min:1',
            'default_length' => 'required|integer|min:1',
            'default_width' => 'required|integer|min:1',
            'default_height' => 'required|integer|min:1',
        ]);

        if (empty($data['shipment_point']) && (empty($data['from_city_code']) || empty($data['from_address']))) {
            return response()->json([
                'message' => 'Откуда отправлять',
                'errors' => ['shipment_point' => ['Укажите пункт приёма СДЭК или город и адрес, откуда забирать заказы']],
            ], 422);
        }

        if (! empty($data['client_secret'])) {
            CdekClient::storeSecret($data['client_secret']);
        }

        foreach (array_diff_key($data, ['client_secret' => true]) as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
            TIntegrationSettings::set(CdekClient::INTEGRATION, $key, $value ?? '', $type);
        }

        return response()->json(['success' => true, 'message' => 'Настройки СДЭК сохранены']);
    }

    /**
     * Check the values from the form; an empty secret means the saved one.
     */
    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'test_mode' => 'boolean',
        ]);

        try {
            (new CdekClient($data))->checkConnection();
        } catch (CdekException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Подключение работает']);
    }

    public function cities(Request $request, CdekClient $client): JsonResponse
    {
        $data = $request->validate(['q' => 'required|string|min:2|max:100']);

        return $this->fromCdek(fn () => collect($client->get('location/cities', ['city' => $data['q'], 'country_codes' => 'RU', 'size' => 10]))
            ->map(fn (array $city) => [
                'code' => $city['code'],
                'name' => trim($city['city'].(empty($city['region']) ? '' : ', '.$city['region'])),
            ])->values());
    }

    public function points(Request $request, CdekClient $client): JsonResponse
    {
        $data = $request->validate(['city_code' => 'required|integer|min:1']);

        return $this->fromCdek(fn () => collect($client->get('deliverypoints', ['city_code' => $data['city_code'], 'type' => 'PVZ']))
            ->map(fn (array $point) => [
                'code' => $point['code'],
                'name' => trim(($point['location']['address'] ?? '').' ('.($point['name'] ?? $point['code']).')'),
            ])->sortBy('name')->values());
    }

    /**
     * The shipment of an order, with what the form needs when there is none yet.
     */
    public function shipment(int $orderId): JsonResponse
    {
        $order = TOrders::findOrFail($orderId);
        $data = (array) $order->addition_data;
        $settings = CdekShipments::settings();

        return response()->json([
            'shipment' => $this->present(TCdekShipment::where('order_id', $orderId)->first()),
            'defaults' => [
                'delivery_point' => $data['cdek_pvz'] ?? null,
                'city_code' => $data['cdek_city_code'] ?? null,
                'address' => $order->delivery_address,
                'length' => $settings['default_length'],
                'width' => $settings['default_width'],
                'height' => $settings['default_height'],
            ],
        ]);
    }

    /**
     * Queue sending the order; the job does the talking to CDEK.
     */
    public function createShipment(Request $request, int $orderId, CdekShipments $shipments): JsonResponse
    {
        $order = TOrders::findOrFail($orderId);

        $data = $request->validate([
            'delivery_point' => 'nullable|string|max:32',
            'city_code' => 'nullable|integer|min:1',
            'address' => 'nullable|string|max:500',
            'weight' => 'nullable|integer|min:1',
            'length' => 'nullable|integer|min:1',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
        ]);

        if (empty($data['delivery_point']) && empty($data['address'])) {
            return response()->json([
                'message' => 'Куда доставить',
                'errors' => ['address' => ['Укажите пункт выдачи или адрес доставки']],
            ], 422);
        }

        try {
            return response()->json(['shipment' => $this->present($shipments->schedule($order, $data))], 202);
        } catch (CdekException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function refreshShipment(int $orderId, CdekShipments $shipments): JsonResponse
    {
        $shipment = TCdekShipment::where('order_id', $orderId)->firstOrFail();

        try {
            $shipments->refresh($shipment);
        } catch (CdekException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['shipment' => $this->present($shipment->fresh())]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function present(?TCdekShipment $shipment): ?array
    {
        if (! $shipment) {
            return null;
        }

        return [
            'state' => $shipment->state,
            'track_number' => $shipment->track_number,
            'tracking_url' => $shipment->trackingUrl(),
            'status' => $shipment->cdek_status_name,
            'status_at' => $shipment->cdek_status_at?->format('d.m.Y H:i'),
            'delivery_point' => $shipment->delivery_point,
            'city_code' => $shipment->city_code,
            'address' => $shipment->address,
            'error' => $shipment->error,
        ];
    }

    private function fromCdek(\Closure $read): JsonResponse
    {
        try {
            return response()->json(['data' => $read()]);
        } catch (CdekException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
