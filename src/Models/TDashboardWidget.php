<?php

namespace HolartWeb\AxoraCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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
    const TYPE_USERS_STATS = 'users_stats';

    const TYPE_POPULAR_PAGES = 'popular_pages';

    const TYPE_RECENT_LOGS = 'recent_logs';

    const TYPE_ORDERS_STATS = 'orders_stats';

    const TYPE_ORDERS_NEW = 'orders_new';

    const TYPE_ORDERS_REVENUE = 'orders_revenue';

    const TYPE_ORDERS_PENDING_PAYMENT = 'orders_pending_payment';

    const TYPE_RECENT_ORDERS = 'recent_orders';

    const TYPE_PROMOCODES_USAGE = 'promocodes_usage';

    const TYPE_SUBSCRIPTIONS_COUNT = 'subscriptions_count';

    const TYPE_SUBSCRIPTIONS_CHART = 'subscriptions_chart';

    const TYPE_REVIEWS_COUNT = 'reviews_count';

    const TYPE_REQUESTS_COUNT = 'requests_count';

    const TYPE_RECENT_REQUESTS = 'recent_requests';

    const TYPE_ORDERS_CHART = 'orders_chart';

    const TYPE_REVENUE_CHART = 'revenue_chart';

    const TYPE_PAGES_VIEWS_CHART = 'pages_views_chart';

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
     */
    public static function getAvailableTypes(): array
    {
        $types = [
            self::TYPE_USERS_STATS => [
                'title' => 'Статистика пользователей',
                'description' => 'Общее количество администраторов',
                'module' => null, // Base module
                'icon' => 'users',
            ],
            self::TYPE_POPULAR_PAGES => [
                'title' => 'Популярные страницы',
                'description' => 'Топ страниц по посещениям',
                'module' => null,
                'icon' => 'chart-bar',
            ],
            self::TYPE_RECENT_LOGS => [
                'title' => 'Последние действия',
                'description' => 'История действий администраторов',
                'module' => null,
                'icon' => 'clock',
            ],
            self::TYPE_PAGES_VIEWS_CHART => [
                'title' => 'График посещений страниц',
                'description' => 'Топ-10 страниц по количеству просмотров',
                'module' => null,
                'icon' => 'chart-bar',
            ],
        ];

        // Commerce module widgets
        if (Schema::hasTable('t_orders')) {
            $types[self::TYPE_ORDERS_STATS] = [
                'title' => 'Общая статистика заказов',
                'description' => 'Количество всех заказов',
                'module' => 'commerce',
                'icon' => 'shopping-cart',
            ];
            $types[self::TYPE_ORDERS_NEW] = [
                'title' => 'Новые заказы',
                'description' => 'Количество заказов со статусом "новый"',
                'module' => 'commerce',
                'icon' => 'bell',
            ];
            $types[self::TYPE_ORDERS_REVENUE] = [
                'title' => 'Выручка',
                'description' => 'Общая сумма заказов',
                'module' => 'commerce',
                'icon' => 'currency-dollar',
            ];
            $types[self::TYPE_ORDERS_PENDING_PAYMENT] = [
                'title' => 'Ожидают оплаты',
                'description' => 'Сумма заказов ожидающих оплаты',
                'module' => 'commerce',
                'icon' => 'credit-card',
            ];
            $types[self::TYPE_RECENT_ORDERS] = [
                'title' => 'Последние заказы',
                'description' => 'Список из 5 последних заказов',
                'module' => 'commerce',
                'icon' => 'list',
            ];
            $types[self::TYPE_PROMOCODES_USAGE] = [
                'title' => 'Использование промокодов',
                'description' => 'Топ промокодов по использованию',
                'module' => 'commerce',
                'icon' => 'tag',
            ];
            $types[self::TYPE_ORDERS_CHART] = [
                'title' => 'График заказов (30 дней)',
                'description' => 'Динамика заказов за последний месяц',
                'module' => 'commerce',
                'icon' => 'chart-line',
            ];
            $types[self::TYPE_REVENUE_CHART] = [
                'title' => 'График выручки (30 дней)',
                'description' => 'Динамика выручки за последний месяц',
                'module' => 'commerce',
                'icon' => 'chart-bar',
            ];
        }

        // Callback module widgets
        if (Schema::hasTable('t_users_emails')) {
            $types[self::TYPE_SUBSCRIPTIONS_COUNT] = [
                'title' => 'Количество подписок',
                'description' => 'Всего активных подписок',
                'module' => 'callback',
                'icon' => 'mail',
            ];
            $types[self::TYPE_SUBSCRIPTIONS_CHART] = [
                'title' => 'График подписок',
                'description' => 'Динамика подписок по дням',
                'module' => 'callback',
                'icon' => 'chart-line',
            ];
            $types[self::TYPE_REVIEWS_COUNT] = [
                'title' => 'Количество отзывов',
                'description' => 'Всего отзывов',
                'module' => 'callback',
                'icon' => 'star',
            ];
            $types[self::TYPE_REQUESTS_COUNT] = [
                'title' => 'Количество обращений',
                'description' => 'Всего обращений от пользователей',
                'module' => 'callback',
                'icon' => 'chat',
            ];
            $types[self::TYPE_RECENT_REQUESTS] = [
                'title' => 'Последние обращения',
                'description' => 'Список из 5 последних обращений',
                'module' => 'callback',
                'icon' => 'inbox',
            ];
        }

        // SEO module widgets
        if (Schema::hasTable('t_pages') && Schema::hasTable('t_page_visits')) {
            $types[self::TYPE_PAGE_VISITS_STATS] = [
                'title' => 'Посещения страниц',
                'description' => 'Топ-5 страниц по посещениям за 30 дней',
                'module' => 'seo',
                'icon' => 'chart-bar',
            ];
        }

        return $types;
    }

    /**
     * Get default widgets for a new admin
     */
    public static function getDefaultWidgets(): array
    {
        $defaults = [
            ['widget_type' => self::TYPE_USERS_STATS, 'position' => 1, 'width' => 4],
            ['widget_type' => self::TYPE_POPULAR_PAGES, 'position' => 2, 'width' => 8],
            ['widget_type' => self::TYPE_RECENT_LOGS, 'position' => 3, 'width' => 12],
        ];

        if (Schema::hasTable('t_orders')) {
            $defaults[] = ['widget_type' => self::TYPE_ORDERS_STATS, 'position' => 4, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_ORDERS_NEW, 'position' => 5, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_ORDERS_REVENUE, 'position' => 6, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_ORDERS_PENDING_PAYMENT, 'position' => 7, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_RECENT_ORDERS, 'position' => 8, 'width' => 6];
            $defaults[] = ['widget_type' => self::TYPE_PROMOCODES_USAGE, 'position' => 9, 'width' => 6];
        }

        if (Schema::hasTable('t_users_emails')) {
            $defaults[] = ['widget_type' => self::TYPE_SUBSCRIPTIONS_COUNT, 'position' => 10, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_REVIEWS_COUNT, 'position' => 11, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_REQUESTS_COUNT, 'position' => 12, 'width' => 3];
            $defaults[] = ['widget_type' => self::TYPE_SUBSCRIPTIONS_CHART, 'position' => 13, 'width' => 6];
            $defaults[] = ['widget_type' => self::TYPE_RECENT_REQUESTS, 'position' => 14, 'width' => 6];
        }

        return $defaults;
    }
}
