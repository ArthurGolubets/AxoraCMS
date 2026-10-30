<?php

namespace HolartWeb\AxoraCMS\Models;

use HolartWeb\AxoraCMS\Services\DashboardMetricsService;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TDashboardWidget extends Model
{
    protected $table = 't_dashboard_widgets';

    protected $fillable = [
        'admin_id',
        'widget_type',
        'position',
        'settings',
        'is_visible',
        'width',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_visible' => 'boolean',
        'position' => 'integer',
        'width' => 'integer',
    ];

    // Widget types
    const TYPE_ATTENTION = 'attention';

    const TYPE_USERS_STATS = 'users_stats';

    const TYPE_RECENT_LOGS = 'recent_logs';

    const TYPE_ORDERS_STATS = 'orders_stats';

    const TYPE_ORDERS_NEW = 'orders_new';

    const TYPE_ORDERS_TODAY = 'orders_today';

    const TYPE_ORDERS_REVENUE = 'orders_revenue';

    const TYPE_REVENUE_PERIOD = 'revenue_period';

    const TYPE_AVERAGE_CHECK = 'average_check';

    const TYPE_CONVERSION = 'conversion';

    const TYPE_ORDERS_PENDING_PAYMENT = 'orders_pending_payment';

    const TYPE_ORDERS_BY_STATUS = 'orders_by_status';

    const TYPE_TOP_PRODUCTS = 'top_products';

    const TYPE_RECENT_ORDERS = 'recent_orders';

    const TYPE_PROMOCODES_USAGE = 'promocodes_usage';

    const TYPE_ORDERS_CHART = 'orders_chart';

    const TYPE_REVENUE_CHART = 'revenue_chart';

    const TYPE_CATALOG_STATS = 'catalog_stats';

    const TYPE_LOW_STOCK = 'low_stock';

    const TYPE_SUBSCRIPTIONS_COUNT = 'subscriptions_count';

    const TYPE_SUBSCRIPTIONS_CHART = 'subscriptions_chart';

    const TYPE_REVIEWS_COUNT = 'reviews_count';

    const TYPE_REQUESTS_COUNT = 'requests_count';

    const TYPE_RECENT_REQUESTS = 'recent_requests';

    const TYPE_VISITS_TODAY = 'visits_today';

    const TYPE_VISITS_CHART = 'visits_chart';

    const TYPE_PAGE_VISITS_STATS = 'page_visits_stats';

    /**
     * Get the administrator who owns this widget
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(TAdministrator::class, 'admin_id');
    }

    /**
     * Get available widget types based on installed modules
     *
     * @return array<string, array{title: string, description: string, module: ?string}>
     */
    public static function getAvailableTypes(): array
    {
        $types = [
            self::TYPE_ATTENTION => ['title' => 'Требует внимания', 'description' => 'Новые заказы, отзывы, обращения и ошибки интеграций', 'module' => null],
            self::TYPE_USERS_STATS => ['title' => 'Администраторы', 'description' => 'Количество администраторов панели', 'module' => null],
            self::TYPE_RECENT_LOGS => ['title' => 'Последние действия', 'description' => 'История действий администраторов', 'module' => null],
        ];

        if (self::hasTable('t_orders')) {
            $types += [
                self::TYPE_ORDERS_TODAY => ['title' => 'Заказы сегодня', 'description' => 'Количество и сумма, сравнение со вчера', 'module' => 'commerce'],
                self::TYPE_REVENUE_PERIOD => ['title' => 'Выручка за 30 дней', 'description' => 'Оплаченные заказы, сравнение с прошлыми 30 днями', 'module' => 'commerce'],
                self::TYPE_AVERAGE_CHECK => ['title' => 'Средний чек', 'description' => 'По оплаченным заказам за 30 дней', 'module' => 'commerce'],
                self::TYPE_ORDERS_NEW => ['title' => 'Новые заказы', 'description' => 'Заказы, ожидающие обработки', 'module' => 'commerce'],
                self::TYPE_ORDERS_STATS => ['title' => 'Все заказы', 'description' => 'Количество заказов за всё время', 'module' => 'commerce'],
                self::TYPE_ORDERS_REVENUE => ['title' => 'Выручка за всё время', 'description' => 'Сумма оплаченных заказов', 'module' => 'commerce'],
                self::TYPE_ORDERS_PENDING_PAYMENT => ['title' => 'Ожидают оплаты', 'description' => 'Сумма неоплаченных и не отменённых заказов', 'module' => 'commerce'],
                self::TYPE_ORDERS_BY_STATUS => ['title' => 'Заказы по статусам', 'description' => 'Сколько заказов на каждом этапе доставки', 'module' => 'commerce'],
                self::TYPE_TOP_PRODUCTS => ['title' => 'Топ товаров', 'description' => 'Самые продаваемые товары за 30 дней', 'module' => 'commerce'],
                self::TYPE_RECENT_ORDERS => ['title' => 'Последние заказы', 'description' => 'Список из 5 последних заказов', 'module' => 'commerce'],
                self::TYPE_PROMOCODES_USAGE => ['title' => 'Использование промокодов', 'description' => 'Топ промокодов по использованию', 'module' => 'commerce'],
                self::TYPE_REVENUE_CHART => ['title' => 'График выручки', 'description' => 'Оплаченные заказы по дням за 30 дней', 'module' => 'commerce'],
                self::TYPE_ORDERS_CHART => ['title' => 'График заказов', 'description' => 'Заказы по дням за 30 дней', 'module' => 'commerce'],
            ];
        }

        if (self::hasTable('t_products')) {
            $types[self::TYPE_CATALOG_STATS] = ['title' => 'Каталог', 'description' => 'Количество товаров и категорий', 'module' => 'shop'];

            if (app(DashboardMetricsService::class)->hasStockColumn()) {
                $types[self::TYPE_LOW_STOCK] = ['title' => 'Заканчивающиеся товары', 'description' => 'Активные товары с остатком 5 и меньше', 'module' => 'commerceml'];
            }
        }

        if (self::hasTable('t_users_emails')) {
            $types += [
                self::TYPE_SUBSCRIPTIONS_COUNT => ['title' => 'Подписки', 'description' => 'Всего и активных подписок', 'module' => 'callback'],
                self::TYPE_SUBSCRIPTIONS_CHART => ['title' => 'График подписок', 'description' => 'Новые подписки по дням за 30 дней', 'module' => 'callback'],
                self::TYPE_REVIEWS_COUNT => ['title' => 'Отзывы', 'description' => 'Всего и на модерации', 'module' => 'callback'],
                self::TYPE_REQUESTS_COUNT => ['title' => 'Обращения', 'description' => 'Всего и за сегодня', 'module' => 'callback'],
                self::TYPE_RECENT_REQUESTS => ['title' => 'Последние обращения', 'description' => 'Список из 5 последних обращений', 'module' => 'callback'],
            ];
        }

        if (self::hasTable('t_page_visits')) {
            $types += [
                self::TYPE_VISITS_TODAY => ['title' => 'Посещения сегодня', 'description' => 'Просмотры и посетители, сравнение со вчера', 'module' => 'seo'],
                self::TYPE_VISITS_CHART => ['title' => 'График посещений', 'description' => 'Просмотры по дням за 30 дней', 'module' => 'seo'],
                self::TYPE_PAGE_VISITS_STATS => ['title' => 'Популярные страницы', 'description' => 'Топ-5 страниц за 30 дней', 'module' => 'seo'],
            ];

            if (self::hasTable('t_orders')) {
                $types[self::TYPE_CONVERSION] = ['title' => 'Конверсия', 'description' => 'Доля посетителей, оформивших заказ, за 30 дней', 'module' => 'seo'];
            }
        }

        return $types;
    }

    /**
     * Get default widgets for a new admin
     *
     * @return array<int, array{widget_type: string, position: int, width: int}>
     */
    public static function getDefaultWidgets(): array
    {
        $layout = [[self::TYPE_ATTENTION, 12]];

        if (self::hasTable('t_orders')) {
            array_push(
                $layout,
                [self::TYPE_ORDERS_TODAY, 3],
                [self::TYPE_REVENUE_PERIOD, 3],
                [self::TYPE_AVERAGE_CHECK, 3],
                [self::TYPE_ORDERS_NEW, 3],
                [self::TYPE_REVENUE_CHART, 8],
                [self::TYPE_ORDERS_BY_STATUS, 4],
                [self::TYPE_RECENT_ORDERS, 6],
                [self::TYPE_TOP_PRODUCTS, 6],
            );
        }

        if (self::hasTable('t_users_emails')) {
            array_push(
                $layout,
                [self::TYPE_REQUESTS_COUNT, 4],
                [self::TYPE_REVIEWS_COUNT, 4],
                [self::TYPE_SUBSCRIPTIONS_COUNT, 4],
                [self::TYPE_RECENT_REQUESTS, 6],
            );
        }

        if (self::hasTable('t_page_visits')) {
            array_push($layout, [self::TYPE_VISITS_CHART, 8], [self::TYPE_PAGE_VISITS_STATS, 4]);
        }

        $layout[] = [self::TYPE_RECENT_LOGS, 12];

        return array_map(fn (array $widget, int $index) => [
            'widget_type' => $widget[0],
            'position' => $index + 1,
            'width' => $widget[1],
        ], $layout, array_keys($layout));
    }

    private static function hasTable(string $table): bool
    {
        return app(SiteCacheService::class)->hasTable($table);
    }
}
