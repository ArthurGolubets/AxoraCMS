<?php

use HolartWeb\AxoraCMS\AxoraCMSServiceProvider;
use HolartWeb\AxoraCMS\Models\Callback\TComments;
use HolartWeb\AxoraCMS\Models\Commerce\TOrders;
use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageAction;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageLog;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageSubscription;
use HolartWeb\AxoraCMS\Models\MessageManager\TMessageTemplate;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Services\OrderService;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['mail.default' => 'array', 'app.url' => 'https://shop.test', 'queue.default' => 'sync']);

    Artisan::call('migrate:fresh', [
        '--path' => ['plugins/axora/database/migrations', 'plugins/axora/database/migrations/messagemanager', 'plugins/axora/database/migrations/integrations'],
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

    TModule::install('commerce', '1.0.0');
    TModule::install('messagemanager', '1.0.0');

    $this->app['router']->setRoutes(new RouteCollection);
    (new ReflectionMethod(AxoraCMSServiceProvider::class, 'registerAdminRoutes'))->invoke(new AxoraCMSServiceProvider($this->app));
    $this->app['router']->getRoutes()->refreshNameLookups();

    $this->admin = TAdministrator::create([
        'name' => 'Super',
        'email' => 'super@example.test',
        'password' => Hash::make('Password123!'),
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $this->items = [['product_id' => 1, 'product_name' => 'Футболка', 'amount' => 2, 'total_price' => 2400]];
    $this->order = ['name' => 'Иван', 'email' => 'ivan@example.com', 'phone' => '+7 900', 'delivery_address' => 'Москва'];
});

function makeTelegramSubscription(string $event, string $text, array $extra = []): TMessageSubscription
{
    $subscription = TMessageSubscription::create(['name' => 'tg', 'event' => $event] + $extra);
    $subscription->actions()->create([
        'channel' => TMessageAction::CHANNEL_TELEGRAM,
        'sort' => 0,
        'config' => ['text' => $text, 'chat_ids' => []],
    ]);

    return $subscription;
}

function enableTelegram(array $chats = ['100', '200']): void
{
    TModule::install('telegram', '1.0.0');
    TIntegrationSettings::set('telegram', 'bot_token', 'TOKEN', 'string');
    TIntegrationSettings::set('telegram', 'chat_ids', $chats, 'array');
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
}

it('sends a telegram message with variables after an order is created', function () {
    enableTelegram();
    makeTelegramSubscription('order.created', "Заказ №#ORDER_ID#\n#PRODUCTS#\nИТОГО: #TOTAL#");

    $order = app(OrderService::class)->createOrder($this->order, $this->items);

    Http::assertSent(fn ($request) => str_contains($request['text'], 'Заказ №'.$order->id)
        && str_contains($request['text'], 'Футболка × 2 — 2 400 ₽')
        && str_contains($request['text'], 'ИТОГО: 2 400 ₽'));
    Http::assertSentCount(2); // both configured chats
    expect(TMessageLog::first())->status->toBe('sent');
});

it('replaces the built-in telegram notification while the module is installed', function () {
    enableTelegram(['100']);

    app(OrderService::class)->createOrder($this->order, $this->items);
    Http::assertNothingSent();

    TModule::uninstall('messagemanager');
    app(OrderService::class)->createOrder($this->order, $this->items);
    Http::assertSentCount(1);
});

it('sends to the chosen chats only and escapes variable values for telegram html', function () {
    enableTelegram();
    $subscription = makeTelegramSubscription('order.created', 'Клиент: #ORDER_NAME#');
    $subscription->actions()->first()->update(['config' => ['text' => '<b>Клиент:</b> #ORDER_NAME#', 'chat_ids' => ['200']]]);

    app(OrderService::class)->createOrder(['name' => '<i>Hack</i>'] + $this->order, $this->items);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['chat_id'] === '200' && $request['text'] === '<b>Клиент:</b> &lt;i&gt;Hack&lt;/i&gt;');
});

it('runs actions in order and sends a template email to the customer', function () {
    enableTelegram(['100']);
    $template = TMessageTemplate::create(['name' => 'Заказ', 'subject' => 'Заказ №#ORDER_ID#', 'body' => '<p>Привет, #ORDER_NAME#</p>#PRODUCTS#']);
    $subscription = TMessageSubscription::create(['name' => 'chain', 'event' => 'order.created']);
    $subscription->actions()->create(['channel' => 'email', 'sort' => 0, 'config' => ['template_id' => $template->id, 'recipients' => '#ORDER_EMAIL#, boss@example.com, not-an-email']]);
    $subscription->actions()->create(['channel' => 'telegram', 'sort' => 1, 'config' => ['text' => 'Готово', 'chat_ids' => []]]);

    $order = app(OrderService::class)->createOrder($this->order, $this->items);

    $logs = TMessageLog::orderBy('id')->get();
    expect($logs->pluck('channel')->all())->toBe(['email', 'email', 'telegram'])
        ->and($logs->pluck('recipient')->take(2)->all())->toBe(['ivan@example.com', 'boss@example.com'])
        ->and($logs[0]->subject)->toBe('Заказ №'.$order->id)
        ->and($logs[0]->body)->toContain('<p>Привет, Иван</p>')->toContain('<table');
});

it('applies subscription conditions and reacts to status changes', function () {
    enableTelegram(['100']);
    makeTelegramSubscription('order.payment_status_changed', 'Оплачен #ORDER_ID#: #OLD_STATUS# → #NEW_STATUS#', [
        'conditions' => [['variable' => 'PAYMENT_STATUS', 'operator' => 'eq', 'value' => 'оплачен']],
    ]);

    $order = app(OrderService::class)->createOrder($this->order, $this->items);
    Http::assertNothingSent();

    $order->update(['payment_status' => TOrders::STATUS_FAILED]);
    Http::assertNothingSent();

    $order->update(['payment_status' => TOrders::STATUS_PAID]);
    Http::assertSent(fn ($request) => $request['text'] === "Оплачен {$order->id}: Ошибка → Оплачен");
});

it('fires the "before" event with draft data', function () {
    enableTelegram(['100']);
    makeTelegramSubscription('order.creating', 'Оформляют: #ORDER_NAME# на #TOTAL#');

    app(OrderService::class)->createOrder($this->order, $this->items);

    Http::assertSent(fn ($request) => $request['text'] === 'Оформляют: Иван на 2 400 ₽');
});

it('reacts to comments and skips inactive subscriptions', function () {
    enableTelegram(['100']);
    Schema::create('t_comments', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('phone')->nullable();
        $t->string('email')->nullable();
        $t->text('comment')->nullable();
        $t->integer('rating')->nullable();
        $t->unsignedBigInteger('product_id')->nullable();
        $t->boolean('is_moderated')->default(false);
        $t->timestamps();
    });
    makeTelegramSubscription('comment.created', 'Комментарий от #COMMENT_NAME#: #COMMENT_TEXT#');
    makeTelegramSubscription('comment.created', 'выключено', ['is_active' => false]);

    TComments::create(['name' => 'Анна', 'comment' => 'Супер']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['text'] === 'Комментарий от Анна: Супер');
});

it('logs a skipped telegram action when the telegram module is missing and never breaks the order', function () {
    makeTelegramSubscription('order.created', 'x');

    $order = app(OrderService::class)->createOrder($this->order, $this->items);

    expect($order->id)->not->toBeNull()
        ->and(TMessageLog::first())->status->toBe('skipped');
});

it('manages subscriptions and templates through the API', function () {
    $template = $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/api/message-manager/templates', ['name' => 'Т', 'subject' => 'Тема', 'body' => '<p>Текст</p>'])
        ->assertCreated()->json('data');

    $payload = [
        'name' => 'Новый заказ',
        'event' => 'order.created',
        'conditions' => [['variable' => '#TOTAL_RAW#', 'operator' => 'gt', 'value' => '1000']],
        'actions' => [
            ['channel' => 'email', 'config' => ['template_id' => $template['id'], 'recipients' => '#ORDER_EMAIL#']],
            ['channel' => 'telegram', 'delay_seconds' => 60, 'config' => ['text' => 'Привет', 'chat_ids' => ['1']]],
        ],
    ];

    $created = $this->actingAs($this->admin, 'admin')->postJson('/admin/api/message-manager/subscriptions', $payload)
        ->assertCreated()
        ->assertJsonPath('data.conditions.0.variable', 'TOTAL_RAW')
        ->assertJsonPath('data.actions.1.delay_seconds', 60)
        ->json('data');

    // Reorder: telegram first, email dropped.
    $this->actingAs($this->admin, 'admin')->putJson("/admin/api/message-manager/subscriptions/{$created['id']}", [
        'name' => 'Новый заказ',
        'event' => 'order.created',
        'actions' => [$created['actions'][1]],
    ])->assertOk()->assertJsonCount(1, 'data.actions');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/api/message-manager/subscriptions', ['name' => 'x', 'event' => 'nope'])
        ->assertStatus(422);
    $this->actingAs($this->admin, 'admin')->postJson('/admin/api/message-manager/subscriptions', [
        'name' => 'x', 'event' => 'order.created',
        'actions' => [['channel' => 'email', 'config' => ['recipients' => 'a@b.c']]],
    ])->assertStatus(422)->assertJsonValidationErrors(['actions.0.config.template_id']);

    $this->actingAs($this->admin, 'admin')->getJson('/admin/api/message-manager/meta')
        ->assertOk()
        ->assertJsonPath('channels.telegram.installed', false)
        ->assertJsonFragment(['code' => 'order.created']);
});

it('stores the block structure of a template next to its html', function () {
    $blocks = ['mode' => 'blocks', 'settings' => ['accent' => '#2563eb'], 'items' => [['type' => 'heading', 'text' => 'Привет, #ORDER_NAME#']]];

    $id = $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/api/message-manager/templates', ['name' => 'Блоки', 'subject' => 'Тема', 'body' => '<h1>Привет</h1>', 'blocks' => $blocks])
        ->assertCreated()->json('data.id');

    $this->actingAs($this->admin, 'admin')->getJson("/admin/api/message-manager/templates/{$id}")
        ->assertJsonPath('data.blocks.items.0.type', 'heading');

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/api/message-manager/templates', ['name' => 'Пусто', 'subject' => 'Тема', 'body' => ''])
        ->assertStatus(422);
});

it('refuses to delete a template that is in use', function () {
    $template = TMessageTemplate::create(['name' => 'Т', 'subject' => 'S', 'body' => 'B']);
    $subscription = TMessageSubscription::create(['name' => 's', 'event' => 'order.created']);
    $subscription->actions()->create(['channel' => 'email', 'config' => ['template_id' => $template->id, 'recipients' => 'a@b.co']]);

    $this->actingAs($this->admin, 'admin')->deleteJson("/admin/api/message-manager/templates/{$template->id}")->assertStatus(422);

    $subscription->delete();
    $this->actingAs($this->admin, 'admin')->deleteJson("/admin/api/message-manager/templates/{$template->id}")->assertOk();
});

it('previews with example values and sends a test email', function () {
    $template = TMessageTemplate::create(['name' => 'Т', 'subject' => 'Заказ №#ORDER_ID#', 'body' => '<p>#ORDER_NAME#</p>']);

    $this->actingAs($this->admin, 'admin')->postJson('/admin/api/message-manager/preview', [
        'event' => 'order.created', 'subject' => $template->subject, 'body' => $template->body,
    ])->assertOk()->assertJsonPath('subject', 'Заказ №1024')->assertJsonPath('body', '<p>Иван Петров</p>');

    $this->actingAs($this->admin, 'admin')->postJson('/admin/api/message-manager/test', [
        'channel' => 'email', 'event' => 'order.created', 'email' => 'me@example.com',
        'config' => ['template_id' => $template->id, 'recipients' => 'ignored@example.com'],
    ])->assertOk();

    expect(TMessageLog::first())->recipient->toBe('me@example.com')->is_test->toBeTrue();
});

it('is closed to guests', function () {
    $this->getJson('/admin/api/message-manager/meta')->assertRedirect();
});
