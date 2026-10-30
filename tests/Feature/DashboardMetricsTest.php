<?php

use HolartWeb\AxoraCMS\AxoraCMSServiceProvider;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Models\TDashboardWidget;
use HolartWeb\AxoraCMS\Services\DashboardMetricsService;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Artisan::call('migrate:fresh', ['--path' => ['plugins/axora/database/migrations'], '--force' => true]);

    Schema::create('t_orders', function ($t) {
        $t->id();
        $t->string('name')->nullable();
        $t->string('email')->nullable();
        $t->string('phone')->nullable();
        $t->string('delivery_status')->default('pending');
        $t->string('payment_status')->default('pending');
        $t->decimal('total_price', 10, 2)->default(0);
        $t->timestamps();
    });
    Schema::create('t_order_items', function ($t) {
        $t->id();
        $t->unsignedBigInteger('order_id');
        $t->string('product_name');
        $t->integer('amount')->default(1);
        $t->decimal('total_price', 10, 2)->default(0);
        $t->timestamps();
    });
    Schema::create('t_page_visits', function ($t) {
        $t->id();
        $t->string('url');
        $t->string('ip_address')->nullable();
        $t->timestamp('visited_at');
    });
    Schema::create('t_comments', function ($t) {
        $t->id();
        $t->boolean('is_moderated')->default(false);
    });
    Schema::create('t_user_requests', function ($t) {
        $t->id();
        $t->timestamp('viewed_at')->nullable();
        $t->timestamps();
    });

    app(SiteCacheService::class)->resetState();

    $this->app['router']->setRoutes(new RouteCollection);
    (new ReflectionMethod(AxoraCMSServiceProvider::class, 'registerAdminRoutes'))->invoke(new AxoraCMSServiceProvider($this->app));
    $this->app['router']->getRoutes()->refreshNameLookups();

    $this->metrics = app(DashboardMetricsService::class);
    $this->admin = dashboardAdmin();
});

function dashboardAdmin(string $role = 'super_admin'): TAdministrator
{
    return TAdministrator::create(['name' => 'Админ', 'email' => $role.'@example.com', 'password' => Hash::make('secret'), 'role' => $role, 'is_active' => true]);
}

function dashboardOrder(float $total, string $payment = 'paid', string $delivery = 'delivered', int $daysAgo = 0): int
{
    return DB::table('t_orders')->insertGetId([
        'total_price' => $total,
        'payment_status' => $payment,
        'delivery_status' => $delivery,
        'created_at' => now()->subDays($daysAgo),
        'updated_at' => now()->subDays($daysAgo),
    ]);
}

it('counts only paid orders as revenue', function () {
    dashboardOrder(1000);
    dashboardOrder(500, 'pending', 'pending');
    dashboardOrder(700, 'refunded', 'cancelled');
    dashboardOrder(300, 'pending', 'cancelled');

    expect($this->metrics->getOrdersStats())->toBe(['total' => 4, 'new' => 1, 'revenue' => 1000.0, 'pending_payment' => 500.0])
        ->and($this->metrics->getRevenuePeriod()['value'])->toBe(1000.0);
});

it('compares the period with the previous one', function () {
    dashboardOrder(1500, daysAgo: 3);
    dashboardOrder(500, daysAgo: 4);
    dashboardOrder(1000, daysAgo: 40);

    expect($this->metrics->getRevenuePeriod())->toBe(['value' => 2000.0, 'change' => 100.0])
        ->and($this->metrics->getAverageCheck())->toBe(['value' => 1000.0, 'orders' => 2, 'change' => 0.0]);
});

it('has no change to show without a previous period', function () {
    dashboardOrder(1000);

    expect($this->metrics->getRevenuePeriod()['change'])->toBeNull()
        ->and($this->metrics->getOrdersToday())->toBe(['count' => 1, 'sum' => 1000.0, 'change' => null]);
});

it('fills the chart with one point per day using a single query', function () {
    dashboardOrder(1000);
    dashboardOrder(500);
    dashboardOrder(200, daysAgo: 2);
    dashboardOrder(900, 'pending', 'pending');
    dashboardOrder(100, daysAgo: 45);

    DB::enableQueryLog();
    $chart = $this->metrics->getRevenueChart();
    $queries = count(DB::getQueryLog());

    expect($chart)->toHaveCount(30)
        ->and($queries)->toBe(1)
        ->and($chart[29])->toBe(['label' => now()->format('d.m'), 'value' => 1500.0])
        ->and($chart[27]['value'])->toBe(200.0)
        ->and($chart[0]['value'])->toBe(0.0);
});

it('calculates conversion from unique visitors and hides it without visits', function () {
    expect($this->metrics->getConversion()['value'])->toBeNull();

    dashboardOrder(1000);
    foreach (['1.1.1.1', '1.1.1.1', '2.2.2.2', '3.3.3.3', '4.4.4.4'] as $ip) {
        DB::table('t_page_visits')->insert(['url' => '/', 'ip_address' => $ip, 'visited_at' => now()]);
    }

    expect($this->metrics->getConversion())->toBe(['value' => 25.0, 'orders' => 1, 'visitors' => 4]);
});

it('ranks products by sales and skips cancelled orders', function () {
    $paid = dashboardOrder(3000);
    $cancelled = dashboardOrder(9000, 'pending', 'cancelled');
    $old = dashboardOrder(9000, daysAgo: 60);

    DB::table('t_order_items')->insert([
        ['order_id' => $paid, 'product_name' => 'Кепка', 'amount' => 1, 'total_price' => 1000],
        ['order_id' => $paid, 'product_name' => 'Футболка', 'amount' => 2, 'total_price' => 2000],
        ['order_id' => $cancelled, 'product_name' => 'Куртка', 'amount' => 1, 'total_price' => 9000],
        ['order_id' => $old, 'product_name' => 'Шапка', 'amount' => 1, 'total_price' => 9000],
    ]);

    expect($this->metrics->getTopProducts())->toBe([
        ['label' => 'Футболка', 'hint' => 'Продано: 2 шт.', 'value' => 2000.0, 'price' => true],
        ['label' => 'Кепка', 'hint' => 'Продано: 1 шт.', 'value' => 1000.0, 'price' => true],
    ]);
});

it('shows only what needs attention and hides closed pages from a manager', function () {
    dashboardOrder(100, 'pending', 'pending');
    DB::table('t_comments')->insert(['is_moderated' => false]);
    DB::table('t_user_requests')->insert(['viewed_at' => now()]);

    $this->actingAs($this->admin, 'admin');
    expect(array_column($this->metrics->getAttentionItems(), 'label'))->toBe(['Новые заказы', 'Отзывы на модерации']);

    $this->actingAs(dashboardAdmin('manager'), 'admin');
    expect(array_column($this->metrics->getAttentionItems(), 'label'))->toBe(['Отзывы на модерации']);
});

it('calculates only the metrics of requested widgets', function () {
    $this->actingAs($this->admin, 'admin')
        ->getJson('/admin/api/dashboard/metrics?types=orders_new,orders_revenue,visits_today,no_such_widget')
        ->assertOk()
        ->assertJsonStructure(['data' => ['orders_stats', 'visits_today']])
        ->assertJsonMissingPath('data.attention')
        ->assertJsonMissingPath('data.no_such_widget');
});

it('shows only widgets that can have data', function () {
    TDashboardWidget::create(['admin_id' => $this->admin->id, 'widget_type' => 'popular_pages', 'position' => 1, 'width' => 6]);
    TDashboardWidget::create(['admin_id' => $this->admin->id, 'widget_type' => 'revenue_chart', 'position' => 2, 'width' => 6]);
    $this->admin->forceFill(['dashboard_initialized' => true])->save();

    $this->actingAs($this->admin, 'admin');

    $this->getJson('/admin/api/dashboard/widgets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.widget_type', 'revenue_chart');

    $this->postJson('/admin/api/dashboard/widgets', ['widget_type' => 'popular_pages'])->assertStatus(422);
    $this->postJson('/admin/api/dashboard/widgets', ['widget_type' => 'low_stock'])->assertStatus(422);
    $this->postJson('/admin/api/dashboard/widgets', ['widget_type' => 'conversion'])->assertCreated();
});

it('gives every available widget a metric, and every default widget is available', function () {
    $available = TDashboardWidget::getAvailableTypes();
    $metrics = $this->metrics->getMetrics(array_keys($available));

    $shared = ['orders_new', 'orders_revenue', 'orders_pending_payment'];
    expect(array_diff(array_keys($available), array_keys($metrics), $shared))->toBe([]);

    expect(array_diff(array_column(TDashboardWidget::getDefaultWidgets(), 'widget_type'), array_keys($available)))->toBe([]);
});

it('offers low stock only when products have a quantity', function () {
    Schema::create('t_products', function ($t) {
        $t->id();
        $t->string('name');
        $t->string('sku');
        $t->boolean('is_active')->default(true);
    });
    app(SiteCacheService::class)->resetState();

    expect(TDashboardWidget::getAvailableTypes())->toHaveKey('catalog_stats')->not->toHaveKey('low_stock');

    Schema::table('t_products', fn ($t) => $t->integer('quantity')->default(0));
    DB::table('t_products')->insert([
        ['name' => 'Мало', 'sku' => 'A', 'quantity' => 2, 'is_active' => true],
        ['name' => 'Много', 'sku' => 'B', 'quantity' => 50, 'is_active' => true],
        ['name' => 'Скрыт', 'sku' => 'C', 'quantity' => 0, 'is_active' => false],
    ]);

    expect(TDashboardWidget::getAvailableTypes())->toHaveKey('low_stock')
        ->and($this->metrics->getLowStock())->toHaveCount(1)
        ->and($this->metrics->getLowStock()[0])->toMatchArray(['label' => 'Мало', 'value' => 2]);
});

it('keeps the dashboard working when one metric fails', function () {
    app(SiteCacheService::class)->hasTable('t_comments');
    Schema::drop('t_comments');
    dashboardOrder(100);

    $metrics = $this->metrics->getMetrics(['reviews_count', 'orders_stats']);

    expect($metrics['reviews_count'])->toBe([])
        ->and($metrics['orders_stats']['total'])->toBe(1);
});
