<?php

use HolartWeb\AxoraCMS\AxoraCMSServiceProvider;
use HolartWeb\AxoraCMS\Models\Callback\TUserRequests;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Log;
use HolartWeb\AxoraCMS\Models\Integrations\TBitrix24Rule;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\Integrations\Bitrix24\Bitrix24Client;
use HolartWeb\AxoraCMS\Services\OrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

const BITRIX24_WEBHOOK = 'https://demo.bitrix24.ru/rest/1/secretkey123/';

beforeEach(function () {
    config(['app.url' => 'https://shop.test']);

    Artisan::call('migrate:fresh', [
        '--path' => ['plugins/axora/database/migrations', 'plugins/axora/database/migrations/integrations', 'plugins/axora/database/migrations/bitrix24'],
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
    Schema::create('t_user_requests', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->text('comment')->nullable();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->timestamp('viewed_at')->nullable();
        $t->timestamps();
    });

    TModule::install('bitrix24', '1.0.0');
    Bitrix24Client::storeWebhook(BITRIX24_WEBHOOK);

    $this->order = ['name' => 'Иван', 'email' => 'ivan@example.com', 'phone' => '+79000000000', 'delivery_price' => 300, 'promocode_discount' => 100];
    $this->items = [['product_id' => 1, 'product_name' => 'Футболка', 'amount' => 2, 'total_price' => 1000]];

    /** @var Router $router */
    $router = app('router');
    $router->setRoutes(new RouteCollection);
    $provider = app()->getProvider(AxoraCMSServiceProvider::class);
    foreach (['registerAdminRoutes', 'registerApiRoutes'] as $method) {
        (new ReflectionMethod($provider, $method))->invoke($provider);
    }
});

function bitrix24Rule(array $overrides = []): TBitrix24Rule
{
    return TBitrix24Rule::create($overrides + [
        'name' => 'Заказы',
        'event' => 'order.created',
        'entity' => 'deal',
        'contact_mode' => 'find_or_create',
        'company_mode' => 'none',
        'send_products' => true,
        'mapping' => [
            'main' => [['field' => 'TITLE', 'value' => 'Заказ №#ORDER_ID#'], ['field' => 'UF_CRM_SOURCE', 'value' => '#SITE_URL#'], ['field' => 'COMMENTS', 'value' => '#ORDER_COMMENT#'], ['field' => 'UF_CRM_UNKNOWN', 'value' => '#NO_SUCH#']],
            'contact' => [['field' => 'NAME', 'value' => '#ORDER_NAME#'], ['field' => 'PHONE', 'value' => '#ORDER_PHONE#'], ['field' => 'EMAIL', 'value' => '#ORDER_EMAIL#']],
            'company' => [['field' => 'TITLE', 'value' => 'ООО #ORDER_NAME#']],
        ],
    ]);
}

function fakeBitrix24(array $overrides = []): void
{
    Http::fake(fn (Request $request) => Http::response(
        $overrides[bitrix24Method($request)] ?? ['result' => match (bitrix24Method($request)) {
            'crm.duplicate.findbycomm', 'crm.company.list' => [],
            'crm.deal.productrows.set' => true,
            default => 77,
        }]
    ));
}

function bitrix24Method(Request $request): string
{
    return preg_replace('#^.*/rest/\d+/[^/]+/(.+)\.json$#', '$1', $request->url());
}

/**
 * Each real request ends once. Here the callbacks must be forgotten, or the kernel
 * of a later test request would run the same jobs again.
 */
function runAfterResponse(): void
{
    app()->terminate();
    (new ReflectionProperty(app(), 'terminatingCallbacks'))->setValue(app(), []);
}

function bitrix24Admin(string $role = 'super_admin'): TAdministrator
{
    return TAdministrator::create(['name' => 'Админ', 'email' => $role.'@example.com', 'password' => Hash::make('secret'), 'role' => $role]);
}

it('creates a contact and a deal with mapped and custom fields after an order', function () {
    fakeBitrix24();
    bitrix24Rule();

    $order = app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    Http::assertSent(function (Request $request) {
        return bitrix24Method($request) === 'crm.contact.add'
            && $request['fields']['NAME'] === 'Иван'
            && $request['fields']['PHONE'] === [['VALUE' => '+79000000000', 'VALUE_TYPE' => 'WORK']];
    });
    Http::assertSent(function (Request $request) use ($order) {
        return bitrix24Method($request) === 'crm.deal.add'
            && $request['fields']['TITLE'] === 'Заказ №'.$order->id
            && $request['fields']['UF_CRM_SOURCE'] === 'https://shop.test'
            && $request['fields']['CONTACT_ID'] === 77
            && ! array_key_exists('COMMENTS', $request['fields'])
            && ! array_key_exists('UF_CRM_UNKNOWN', $request['fields']);
    });

    expect(TBitrix24Log::first())->status->toBe('success')->entity_id->toBe(77)->contact_id->toBe(77);
});

it('sends product rows that add up to the order total', function () {
    fakeBitrix24();
    bitrix24Rule();

    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    Http::assertSent(function (Request $request) {
        if (bitrix24Method($request) !== 'crm.deal.productrows.set') {
            return false;
        }

        $total = array_sum(array_map(fn ($row) => $row['PRICE'] * $row['QUANTITY'], $request['rows']));

        return $request['id'] === 77 && $total == 1200 && $request['rows'][1]['PRODUCT_NAME'] === 'Доставка';
    });
});

it('reuses an existing contact instead of creating a duplicate', function () {
    fakeBitrix24(['crm.duplicate.findbycomm' => ['result' => ['CONTACT' => [15, 9]]]]);
    bitrix24Rule();

    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    Http::assertNotSent(fn (Request $request) => bitrix24Method($request) === 'crm.contact.add');
    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.deal.add' && $request['fields']['CONTACT_ID'] === 9);
});

it('creates no contact or company when the rule says so and links the company to the contact', function () {
    fakeBitrix24();
    bitrix24Rule(['entity' => 'lead', 'contact_mode' => 'none', 'company_mode' => 'none', 'send_products' => false]);
    bitrix24Rule(['name' => 'Компания', 'entity' => 'none', 'contact_mode' => 'create', 'company_mode' => 'create']);

    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.lead.add' && ! isset($request['fields']['CONTACT_ID']));
    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.company.add' && $request['fields']['TITLE'] === 'ООО Иван');
    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.contact.add' && $request['fields']['COMPANY_ID'] === 77);
    Http::assertSentCount(3);
});

it('handles user requests and skips inactive rules', function () {
    fakeBitrix24();
    bitrix24Rule([
        'event' => 'user_request.created', 'entity' => 'lead', 'contact_mode' => 'none', 'send_products' => false,
        'mapping' => ['main' => [['field' => 'TITLE', 'value' => 'Заявка от #REQUEST_NAME#']]],
    ]);
    bitrix24Rule(['event' => 'user_request.created', 'is_active' => false]);

    TUserRequests::create(['name' => 'Мария', 'phone' => '+7']);
    runAfterResponse();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.lead.add' && $request['fields']['TITLE'] === 'Заявка от Мария');
});

it('does nothing while the module is not installed', function () {
    fakeBitrix24();
    bitrix24Rule();
    TModule::uninstall('bitrix24');

    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    Http::assertNothingSent();
});

it('never breaks the order, keeps created records and retries without duplicating them', function () {
    $portalIsBroken = true;
    Http::fake(function (Request $request) use (&$portalIsBroken) {
        return Http::response(match (true) {
            $portalIsBroken && bitrix24Method($request) === 'crm.deal.add' => ['error' => 'ERROR_CORE', 'error_description' => 'Не заполнено поле'],
            bitrix24Method($request) === 'crm.duplicate.findbycomm' => ['result' => []],
            default => ['result' => $portalIsBroken ? 55 : 88],
        });
    });
    bitrix24Rule(['send_products' => false]);

    $order = app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    expect($order->exists)->toBeTrue();
    $log = TBitrix24Log::first();
    expect($log)->status->toBe('failed')->message->toBe('Не заполнено поле')->contact_id->toBe(55);

    $portalIsBroken = false;

    $this->actingAs(bitrix24Admin(), 'admin')
        ->postJson("/admin/api/integrations/bitrix24/logs/{$log->id}/retry")
        ->assertOk();

    $contactsCreated = Http::recorded(fn (Request $request) => bitrix24Method($request) === 'crm.contact.add');
    expect($contactsCreated)->toHaveCount(1);
    Http::assertSent(fn (Request $request) => bitrix24Method($request) === 'crm.deal.add' && $request['fields']['CONTACT_ID'] === 55);
    expect(TBitrix24Log::count())->toBe(1)->and(TBitrix24Log::first()->entity_id)->toBe(88);
});

it('marks the log partial when only the product rows fail', function () {
    fakeBitrix24(['crm.deal.productrows.set' => ['error' => 'ERROR_CORE', 'error_description' => 'нет прав']]);
    bitrix24Rule();

    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();

    expect(TBitrix24Log::first())->status->toBe('partial')->entity_id->toBe(77);
});

it('keeps the webhook secret: encrypted at rest, masked in the API and in errors', function () {
    $stored = TIntegrationSettings::where('integration_type', 'bitrix24')->value('value');
    expect($stored)->not->toContain('secretkey123');

    Http::fake(fn () => throw new ConnectionException('cURL error for '.BITRIX24_WEBHOOK));
    bitrix24Rule(['send_products' => false]);
    app(OrderService::class)->createOrder($this->order, $this->items);
    runAfterResponse();
    expect(TBitrix24Log::first()->message)->not->toContain('secretkey123');

    $this->actingAs(bitrix24Admin(), 'admin')
        ->getJson('/admin/api/integrations/bitrix24')
        ->assertOk()
        ->assertJsonPath('configured', true)
        ->assertJsonPath('webhook_masked', 'https://demo.bitrix24.ru/rest/1/••••••/');
});

it('validates the webhook and checks its crm permission', function () {
    $admin = bitrix24Admin();

    $this->actingAs($admin, 'admin')
        ->postJson('/admin/api/integrations/bitrix24', ['webhook_url' => 'http://internal.local/hook'])
        ->assertStatus(422);

    $scopes = ['user', 'task'];
    Http::fake(function () use (&$scopes) {
        return Http::response(['result' => $scopes]);
    });
    $this->postJson('/admin/api/integrations/bitrix24/test', ['webhook_url' => BITRIX24_WEBHOOK])->assertStatus(422);

    $scopes = ['crm'];
    $this->postJson('/admin/api/integrations/bitrix24/test')->assertOk();
});

it('returns portal fields with custom ones and list values, without read-only and link fields', function () {
    Http::fake(fn (Request $request) => Http::response(['result' => bitrix24Method($request) === 'batch'
        ? ['result' => [
            'lead' => [
                'ID' => ['type' => 'integer', 'isReadOnly' => true, 'title' => 'ID'],
                'TITLE' => ['type' => 'string', 'isRequired' => true, 'title' => 'Название'],
                'CONTACT_ID' => ['type' => 'crm_contact', 'title' => 'Контакт'],
                'SOURCE_ID' => ['type' => 'crm_status', 'statusType' => 'SOURCE', 'title' => 'Источник'],
                'UF_CRM_1' => ['type' => 'enumeration', 'title' => 'UF_CRM_1', 'formLabel' => 'Город', 'items' => [['ID' => '5', 'VALUE' => 'Москва']]],
            ],
            'deal' => ['CATEGORY_ID' => ['type' => 'crm_category', 'title' => 'Воронка']],
            'contact' => [],
            'company' => [],
            'categories' => ['categories' => [['id' => 0, 'name' => 'Общая']]],
        ], 'result_error' => []]
        : [['ENTITY_ID' => 'SOURCE', 'STATUS_ID' => 'WEB', 'NAME' => 'Сайт']]]));

    $response = $this->actingAs(bitrix24Admin(), 'admin')->getJson('/admin/api/integrations/bitrix24/fields')->assertOk();

    $lead = collect($response->json('data.lead'))->keyBy('code');
    expect($lead->keys()->all())->toBe(['SOURCE_ID', 'TITLE', 'UF_CRM_1'])
        ->and($lead['UF_CRM_1'])->toMatchArray(['label' => 'Город', 'custom' => true, 'options' => [['id' => '5', 'label' => 'Москва']]])
        ->and($lead['SOURCE_ID']['options'])->toBe([['id' => 'WEB', 'label' => 'Сайт']])
        ->and($response->json('data.deal.0.options'))->toBe([['id' => '0', 'label' => 'Общая']]);
});

it('manages rules through the API for administrators only', function () {
    $payload = [
        'name' => 'Заявки', 'event' => 'user_request.created', 'entity' => 'lead',
        'contact_mode' => 'create', 'company_mode' => 'none',
        'mapping' => ['main' => [['field' => 'TITLE', 'value' => '#REQUEST_NAME#']], 'contact' => [], 'company' => []],
    ];

    $this->actingAs(bitrix24Admin('manager'), 'admin')->postJson('/admin/api/integrations/bitrix24/rules', $payload)->assertForbidden();

    $this->actingAs(bitrix24Admin(), 'admin');
    $id = $this->postJson('/admin/api/integrations/bitrix24/rules', $payload)->assertCreated()->json('data.id');

    $this->postJson('/admin/api/integrations/bitrix24/rules', ['entity' => 'none', 'contact_mode' => 'none', 'company_mode' => 'none'] + $payload)->assertStatus(422);
    $this->postJson("/admin/api/integrations/bitrix24/rules/{$id}/toggle")->assertJsonPath('is_active', false);
    $this->deleteJson("/admin/api/integrations/bitrix24/rules/{$id}")->assertOk();
    expect(TBitrix24Rule::count())->toBe(0);
});

it('installs and uninstalls cleanly, leaving the settings of other integrations alone', function () {
    TIntegrationSettings::set('telegram', 'bot_token', 'TOKEN', 'string');

    Artisan::call('axoracms:bitrix24-uninstall');

    expect(Schema::hasTable('t_bitrix24_rules'))->toBeFalse()
        ->and(TModule::isInstalled('bitrix24'))->toBeFalse()
        ->and(Bitrix24Client::storedWebhook())->toBeNull()
        ->and(TIntegrationSettings::get('telegram', 'bot_token'))->toBe('TOKEN');

    expect(Artisan::call('axoracms:bitrix24-install'))->toBe(0)
        ->and(Schema::hasTable('t_bitrix24_rules'))->toBeTrue()
        ->and(Schema::hasTable('t_bitrix24_logs'))->toBeTrue()
        ->and(TModule::isInstalled('bitrix24'))->toBeTrue();
});
