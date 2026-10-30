<?php

use HolartWeb\AxoraCMS\AxoraCMSServiceProvider;
use HolartWeb\AxoraCMS\Jobs\CreateCdekShipmentJob;
use HolartWeb\AxoraCMS\Jobs\RefreshCdekShipmentJob;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Commerce\TOrdersData;
use HolartWeb\AxoraCMS\Models\Integrations\TCdekShipment;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekClient;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekException;
use HolartWeb\AxoraCMS\Services\Integrations\Cdek\CdekShipments;
use HolartWeb\AxoraCMS\Services\OrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

const CDEK_UUID = '72753031-0000-0000-0000-000000000001';

beforeEach(function () {
    config(['cache.default' => 'array', 'queue.default' => 'sync']);
    Cache::flush();

    Artisan::call('migrate:fresh', [
        '--path' => ['plugins/axora/database/migrations', 'plugins/axora/database/migrations/integrations', 'plugins/axora/database/migrations/cdek'],
        '--force' => true,
    ]);

    Schema::create('t_orders', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->json('addition_data')->nullable();
        $t->string('delivery_type')->nullable();
        $t->string('delivery_address')->nullable();
        $t->string('delivery_status')->default('pending');
        $t->string('payment_type')->nullable();
        $t->unsignedBigInteger('payment_id')->nullable();
        $t->string('payment_status')->default('pending');
        $t->decimal('total_price', 10, 2)->default(0);
        $t->decimal('goods_price', 10, 2)->default(0);
        $t->decimal('delivery_price', 10, 2)->default(0);
        $t->unsignedBigInteger('promocode_id')->nullable();
        $t->decimal('promocode_discount', 10, 2)->default(0);
        $t->text('comments')->nullable();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->timestamps();
    });
    Schema::create('t_order_items', function ($t) {
        $t->id();
        $t->unsignedBigInteger('order_id');
        $t->unsignedBigInteger('product_id')->nullable();
        $t->unsignedBigInteger('variant_id')->nullable();
        $t->json('variant_data')->nullable();
        $t->string('product_name');
        $t->integer('amount')->default(1);
        $t->decimal('total_price', 10, 2)->default(0);
        $t->string('set_group')->nullable();
        $t->string('set_role')->nullable();
        $t->timestamps();
    });
    Schema::create('t_orders_data', function ($t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->string('type')->default('string');
        $t->timestamps();
    });

    TModule::install('commerce', '1.0.0');
    TModule::install('cdek', '1.0.0');
    TIntegrationSettings::set('cdek', 'client_id', 'ACCOUNT', 'string');
    CdekClient::storeSecret('SECRET');
    TIntegrationSettings::set('cdek', 'shipment_point', 'MSK1', 'string');
    TIntegrationSettings::set('cdek', 'auto_create', true, 'boolean');
    TOrdersData::setValue(CdekShipments::ENABLED_SETTING, true, TOrdersData::TYPE_BOOLEAN);

    $this->orderData = ['name' => 'Иван', 'email' => 'ivan@example.com', 'phone' => '+79000000000', 'delivery_type' => 'cdek', 'delivery_address' => 'Москва, Тверская, 1', 'addition_data' => ['cdek_pvz' => 'MSK55']];
    $this->items = [['product_id' => 1, 'product_name' => 'Футболка', 'amount' => 2, 'total_price' => 1000]];

    $this->app['router']->setRoutes(new RouteCollection);
    (new ReflectionMethod(AxoraCMSServiceProvider::class, 'registerAdminRoutes'))->invoke(new AxoraCMSServiceProvider($this->app));
    $this->app['router']->getRoutes()->refreshNameLookups();
});

function cdekAdmin(): TAdministrator
{
    return TAdministrator::create(['name' => 'Админ', 'email' => 'a@example.com', 'password' => Hash::make('secret'), 'role' => 'super_admin', 'is_active' => true]);
}

function cdekPath(Request $request): string
{
    return (string) preg_replace('#^https://api[^/]*/v2/#', '', explode('?', $request->url())[0]);
}

/**
 * @param  array<string, mixed>  $order  body of GET /orders/{uuid}
 */
function fakeCdek(array &$order = []): void
{
    Http::fake(function (Request $request) use (&$order) {
        return Http::response(match (true) {
            cdekPath($request) === 'oauth/token' => ['access_token' => 'TOKEN', 'expires_in' => 3599],
            cdekPath($request) === 'orders' => ['entity' => ['uuid' => CDEK_UUID], 'requests' => [['state' => 'ACCEPTED']]],
            default => $order,
        });
    });
}

function cdekOrderWithNumber(string $status = 'CREATED', string $name = 'Создан'): array
{
    return ['entity' => ['uuid' => CDEK_UUID, 'cdek_number' => '1106321645', 'statuses' => [
        ['code' => 'ACCEPTED', 'name' => 'Принят', 'date_time' => '2026-10-30T10:00:00+0000'],
        ['code' => $status, 'name' => $name, 'date_time' => '2026-10-30T11:00:00+0000'],
    ]], 'requests' => [['state' => 'SUCCESSFUL']]];
}

it('only queues the shipment while the order is placed', function () {
    Queue::fake();
    Http::fake();

    $order = app(OrderService::class)->createOrder($this->orderData, $this->items);

    Queue::assertPushed(CreateCdekShipmentJob::class, 1);
    Http::assertNothingSent();
    expect(TCdekShipment::where('order_id', $order->id)->first())->state->toBe('queued')->delivery_point->toBe('MSK55');
});

it('leaves other orders alone, and honours the switches', function () {
    Queue::fake();

    app(OrderService::class)->createOrder(['delivery_type' => 'courier'] + $this->orderData, $this->items);

    TIntegrationSettings::set('cdek', 'auto_create', false, 'boolean');
    app(OrderService::class)->createOrder($this->orderData, $this->items);

    TIntegrationSettings::set('cdek', 'auto_create', true, 'boolean');
    TOrdersData::setValue(CdekShipments::ENABLED_SETTING, false, TOrdersData::TYPE_BOOLEAN);
    app(OrderService::class)->createOrder($this->orderData, $this->items);

    Queue::assertNothingPushed();
});

it('sends the order to the pickup point and picks up the track number and status', function () {
    $cdekOrder = cdekOrderWithNumber();
    fakeCdek($cdekOrder);

    $order = app(OrderService::class)->createOrder($this->orderData, $this->items);

    Http::assertSent(function (Request $request) use ($order) {
        return cdekPath($request) === 'orders'
            && $request->hasHeader('Authorization', 'Bearer TOKEN')
            && $request['number'] === (string) $order->id
            && $request['tariff_code'] === 136
            && $request['shipment_point'] === 'MSK1'
            && $request['delivery_point'] === 'MSK55'
            && $request['recipient']['phones'][0]['number'] === '+79000000000'
            && $request['packages'][0]['weight'] === 1000
            && $request['packages'][0]['items'][0]['cost'] == 500
            && $request['packages'][0]['items'][0]['payment']['value'] === 0;
    });

    expect(TCdekShipment::first())
        ->state->toBe('active')
        ->uuid->toBe(CDEK_UUID)
        ->track_number->toBe('1106321645')
        ->cdek_status->toBe('CREATED')
        ->cdek_status_name->toBe('Создан')
        ->and($order->fresh()->delivery_status)->toBe('processing');
});

it('uses the door tariff and collects payment on delivery when asked to', function () {
    TIntegrationSettings::set('cdek', 'cash_on_delivery', true, 'boolean');
    $cdekOrder = cdekOrderWithNumber();
    fakeCdek($cdekOrder);

    app(OrderService::class)->createOrder([
        'addition_data' => ['cdek_city_code' => 44], 'delivery_price' => 300, 'promocode_discount' => 100,
    ] + $this->orderData, $this->items);

    Http::assertSent(function (Request $request) {
        return cdekPath($request) === 'orders'
            && $request['tariff_code'] === 137
            && $request['to_location'] === ['code' => 44, 'address' => 'Москва, Тверская, 1']
            && $request['delivery_recipient_cost']['value'] == 300
            && $request['packages'][0]['items'][0]['payment']['value'] == 450;
    });
});

it('keeps asking for the number with a delay until CDEK issues it', function () {
    Queue::fake();
    $cdekOrder = ['entity' => ['uuid' => CDEK_UUID, 'statuses' => []], 'requests' => [['state' => 'ACCEPTED']]];
    fakeCdek($cdekOrder);
    $order = TOrders::create($this->orderData);
    $shipment = TCdekShipment::create(['order_id' => $order->id, 'state' => 'accepted', 'uuid' => CDEK_UUID]);

    (new RefreshCdekShipmentJob($shipment->id))->handle(app(CdekShipments::class));
    Queue::assertPushed(RefreshCdekShipmentJob::class, fn ($job) => $job->attempt === 2 && $job->delay !== null);

    (new RefreshCdekShipmentJob($shipment->id, RefreshCdekShipmentJob::MAX_ATTEMPTS))->handle(app(CdekShipments::class));
    Queue::assertPushed(RefreshCdekShipmentJob::class, 1);
});

it('shows why CDEK rejected the order and lets the manager send it again', function () {
    $cdekOrder = ['entity' => ['uuid' => CDEK_UUID], 'requests' => [['state' => 'INVALID', 'errors' => [['message' => 'Неверный код ПВЗ']]]]];
    fakeCdek($cdekOrder);

    $order = app(OrderService::class)->createOrder($this->orderData, $this->items);

    expect(TCdekShipment::first())->state->toBe('error')->error->toBe('Неверный код ПВЗ');

    $cdekOrder = cdekOrderWithNumber();

    $this->actingAs(cdekAdmin(), 'admin')
        ->postJson("/admin/api/integrations/cdek/orders/{$order->id}", ['delivery_point' => 'MSK77'])
        ->assertStatus(202);

    expect(TCdekShipment::count())->toBe(1)
        ->and(TCdekShipment::first())->state->toBe('active')->delivery_point->toBe('MSK77')->error->toBeNull();
});

it('does not send an order without a phone and refuses a second shipment', function () {
    Http::fake();
    $order = app(OrderService::class)->createOrder(['phone' => null] + $this->orderData, $this->items);

    expect(TCdekShipment::first())->state->toBe('error')->error->toContain('телефон');

    TCdekShipment::first()->update(['state' => 'active', 'error' => null]);
    $this->actingAs(cdekAdmin(), 'admin')
        ->postJson("/admin/api/integrations/cdek/orders/{$order->id}", ['delivery_point' => 'MSK77'])
        ->assertStatus(422);
});

it('lets the job retry a network failure, then records the error', function () {
    $order = TOrders::create($this->orderData);
    $order->items()->create(['product_id' => 1, 'product_name' => 'Футболка', 'amount' => 1, 'total_price' => 100]);
    $shipment = TCdekShipment::create(['order_id' => $order->id, 'state' => 'queued', 'delivery_point' => 'MSK55']);
    Http::fake(fn (Request $request) => cdekPath($request) === 'oauth/token'
        ? Http::response(['access_token' => 'TOKEN', 'expires_in' => 3599])
        : throw new ConnectionException('down'));

    $job = new CreateCdekShipmentJob($shipment->id);

    expect(fn () => $job->handle(app(CdekShipments::class)))->toThrow(CdekException::class);
    expect($shipment->fresh()->state)->toBe('queued');

    $job->failed(new RuntimeException('Не удалось подключиться к СДЭК'));
    expect($shipment->fresh())->state->toBe('error')->error->toBe('Не удалось подключиться к СДЭК');
});

it('finishes a delivered shipment and moves the order status', function () {
    $cdekOrder = cdekOrderWithNumber('DELIVERED', 'Вручен');
    fakeCdek($cdekOrder);
    $order = TOrders::create($this->orderData);
    TCdekShipment::create(['order_id' => $order->id, 'state' => 'active', 'uuid' => CDEK_UUID]);

    $this->actingAs(cdekAdmin(), 'admin')
        ->postJson("/admin/api/integrations/cdek/orders/{$order->id}/refresh")
        ->assertOk()
        ->assertJsonPath('shipment.status', 'Вручен')
        ->assertJsonPath('shipment.track_number', '1106321645')
        ->assertJsonPath('shipment.tracking_url', 'https://www.cdek.ru/ru/tracking?order_id=1106321645');

    expect(TCdekShipment::first()->state)->toBe('finished')
        ->and($order->fresh()->delivery_status)->toBe('delivered');
});

it('syncs only shipments still on their way', function () {
    Queue::fake();
    $order = TOrders::create($this->orderData);
    $active = TCdekShipment::create(['order_id' => $order->id, 'state' => 'active', 'uuid' => CDEK_UUID]);
    TCdekShipment::create(['order_id' => $order->id + 1, 'state' => 'finished', 'uuid' => CDEK_UUID]);
    TCdekShipment::create(['order_id' => $order->id + 2, 'state' => 'error']);

    Artisan::call('axoracms:cdek-sync');

    Queue::assertPushed(RefreshCdekShipmentJob::class, 1);
    Queue::assertPushed(RefreshCdekShipmentJob::class, fn ($job) => $job->shipmentId === $active->id);
});

it('uses the test server in test mode and reuses the token', function () {
    TIntegrationSettings::set('cdek', 'test_mode', true, 'boolean');
    $cdekOrder = cdekOrderWithNumber();
    fakeCdek($cdekOrder);

    app(CdekClient::class)->get('orders/'.CDEK_UUID);
    app(CdekClient::class)->get('orders/'.CDEK_UUID);

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.edu.cdek.ru/v2/'));
    expect(Http::recorded(fn (Request $request) => cdekPath($request) === 'oauth/token'))->toHaveCount(1);
});

it('keeps the secret out of the API and validates the settings', function () {
    expect(TIntegrationSettings::where('integration_type', 'cdek')->where('key', 'client_secret')->value('value'))->not->toContain('SECRET');

    $this->actingAs(cdekAdmin(), 'admin');

    $response = $this->getJson('/admin/api/integrations/cdek')->assertOk()->assertJsonPath('has_secret', true);
    expect($response->getContent())->not->toContain('SECRET')->not->toContain('client_secret');

    $payload = ['client_id' => 'NEW', 'tariff_pvz' => 136, 'tariff_door' => 137, 'default_weight' => 500, 'default_length' => 20, 'default_width' => 20, 'default_height' => 10];

    $this->postJson('/admin/api/integrations/cdek', $payload)->assertStatus(422);
    $this->postJson('/admin/api/integrations/cdek', $payload + ['shipment_point' => 'MSK2'])->assertOk();

    expect(TIntegrationSettings::get('cdek', 'client_id'))->toBe('NEW')
        ->and(CdekClient::storedSecret())->toBe('SECRET');
});

it('offers delivery type cdek only while the module is installed', function () {
    expect(TOrders::deliveryTypes())->toContain('cdek');

    $this->actingAs(cdekAdmin(), 'admin')
        ->getJson('/admin/api/orders-settings')
        ->assertOk()
        ->assertJsonPath('cdek_available', true);

    TModule::uninstall('cdek');
    expect(TOrders::deliveryTypes())->not->toContain('cdek');
});

it('needs the commerce module, and uninstalls without touching other integrations', function () {
    TIntegrationSettings::set('telegram', 'bot_token', 'TOKEN', 'string');

    Artisan::call('axoracms:cdek-uninstall');

    expect(Schema::hasTable('t_cdek_shipments'))->toBeFalse()
        ->and(TModule::isInstalled('cdek'))->toBeFalse()
        ->and(CdekClient::storedSecret())->toBeNull()
        ->and(TOrdersData::getValue(CdekShipments::ENABLED_SETTING))->toBeNull()
        ->and(TIntegrationSettings::get('telegram', 'bot_token'))->toBe('TOKEN');

    TModule::uninstall('commerce');
    expect(Artisan::call('axoracms:cdek-install'))->toBe(1);

    TModule::install('commerce', '1.0.0');
    expect(Artisan::call('axoracms:cdek-install'))->toBe(0)
        ->and(Schema::hasTable('t_cdek_shipments'))->toBeTrue()
        ->and(TModule::isInstalled('cdek'))->toBeTrue();
});
