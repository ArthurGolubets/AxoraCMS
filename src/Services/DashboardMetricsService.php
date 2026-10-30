<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TAdministrator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Numbers of the admin dashboard.
 *
 * Only the metrics of widgets actually placed on the dashboard are calculated.
 * Revenue means paid orders everywhere: an unpaid or cancelled order is not money.
 */
class DashboardMetricsService
{
    private const PERIOD_DAYS = 30;

    private const LOW_STOCK_LIMIT = 5;

    private const DELIVERY_LABELS = [
        'pending' => 'Ожидают обработки',
        'processing' => 'В обработке',
        'shipped' => 'Отправлены',
        'delivered' => 'Доставлены',
        'cancelled' => 'Отменены',
    ];

    /**
     * Widgets showing a part of a shared metric.
     */
    private const SHARED_METRICS = [
        'orders_new' => 'orders_stats',
        'orders_revenue' => 'orders_stats',
        'orders_pending_payment' => 'orders_stats',
    ];

    /**
     * Metric of each widget type => method calculating it.
     */
    private const METRICS = [
        'attention' => 'getAttentionItems',
        'users_stats' => 'getUsersStats',
        'recent_logs' => 'getRecentLogs',
        'orders_stats' => 'getOrdersStats',
        'orders_today' => 'getOrdersToday',
        'revenue_period' => 'getRevenuePeriod',
        'average_check' => 'getAverageCheck',
        'conversion' => 'getConversion',
        'orders_by_status' => 'getOrdersByStatus',
        'top_products' => 'getTopProducts',
        'recent_orders' => 'getRecentOrders',
        'promocodes_usage' => 'getPromocodesUsage',
        'orders_chart' => 'getOrdersChart',
        'revenue_chart' => 'getRevenueChart',
        'catalog_stats' => 'getCatalogStats',
        'low_stock' => 'getLowStock',
        'subscriptions_count' => 'getSubscriptionsCount',
        'subscriptions_chart' => 'getSubscriptionsChart',
        'reviews_count' => 'getReviewsCount',
        'requests_count' => 'getRequestsCount',
        'recent_requests' => 'getRecentRequests',
        'visits_today' => 'getVisitsToday',
        'visits_chart' => 'getVisitsChart',
        'page_visits_stats' => 'getPageVisitsStats',
    ];

    /**
     * Metrics for the given widget types.
     *
     * @param  array<int, string>  $widgetTypes
     * @return array<string, mixed> metric => data
     */
    public function getMetrics(array $widgetTypes): array
    {
        $metrics = [];

        foreach ($widgetTypes as $type) {
            $metric = self::SHARED_METRICS[$type] ?? $type;

            if (isset(self::METRICS[$metric]) && ! array_key_exists($metric, $metrics)) {
                $metrics[$metric] = $this->calculate($metric);
            }
        }

        return $metrics;
    }

    /**
     * One broken metric must not take the whole dashboard down with it.
     */
    private function calculate(string $metric): mixed
    {
        try {
            return $this->{self::METRICS[$metric]}();
        } catch (\Throwable $e) {
            Log::warning("Dashboard metric {$metric} failed: ".$e->getMessage());

            return [];
        }
    }

    /**
     * What is waiting for a person right now. Only non-zero rows are returned, and
     * a manager does not see rows leading to pages closed for that role.
     *
     * @return array<int, array{label: string, value: int, link: string}>
     */
    public function getAttentionItems(): array
    {
        $admin = Auth::guard('admin')->user();
        $isPrivileged = ! $admin || ! $admin->isManager();
        $items = [];

        if ($isPrivileged && $this->hasTable('t_orders')) {
            $items[] = ['label' => 'Новые заказы', 'value' => DB::table('t_orders')->where('delivery_status', 'pending')->count(), 'link' => '/orders'];
        }

        if ($this->hasTable('t_comments')) {
            $items[] = ['label' => 'Отзывы на модерации', 'value' => DB::table('t_comments')->where('is_moderated', false)->count(), 'link' => '/comments'];
        }

        if ($this->hasTable('t_user_requests')) {
            $items[] = ['label' => 'Непросмотренные обращения', 'value' => DB::table('t_user_requests')->whereNull('viewed_at')->count(), 'link' => '/user-requests'];
        }

        if ($this->hasTable('t_custom_form_submissions')) {
            $items[] = ['label' => 'Новые записи форм', 'value' => DB::table('t_custom_form_submissions')->whereNull('viewed_at')->count(), 'link' => '/custom-forms'];
        }

        if ($isPrivileged && $this->hasTable('t_cdek_shipments')) {
            $items[] = ['label' => 'Заказы с ошибкой СДЭК', 'value' => DB::table('t_cdek_shipments')->where('state', 'error')->count(), 'link' => '/orders'];
        }

        if ($isPrivileged && $this->hasTable('t_bitrix24_logs')) {
            $items[] = ['label' => 'Ошибки отправки в Битрикс24', 'value' => DB::table('t_bitrix24_logs')->where('status', 'failed')->count(), 'link' => '/integrations/bitrix24?tab=logs'];
        }

        return array_values(array_filter($items, fn (array $item) => $item['value'] > 0));
    }

    /**
     * Get users statistics
     *
     * @return array{total: int, active: int, inactive: int}
     */
    public function getUsersStats(): array
    {
        return [
            'total' => TAdministrator::count(),
            'active' => TAdministrator::where('is_active', true)->count(),
            'inactive' => TAdministrator::where('is_active', false)->count(),
        ];
    }

    /**
     * Get recent admin actions (logs)
     */
    public function getRecentLogs(int $limit = 6): array
    {
        if (! $this->hasTable('t_admin_actions')) {
            return [];
        }

        return TAdminAction::with('admin:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'admin_name' => $log->admin->name ?? 'Unknown',
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'description' => $log->description,
                    'created_at' => $log->created_at->format('d.m.Y H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get orders statistics
     */
    public function getOrdersStats(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        return [
            'total' => DB::table('t_orders')->count(),
            'new' => DB::table('t_orders')->where('delivery_status', 'pending')->count(),
            'revenue' => (float) $this->paidOrders()->sum('total_price'),
            'pending_payment' => (float) DB::table('t_orders')
                ->where('payment_status', 'pending')
                ->where('delivery_status', '!=', 'cancelled')
                ->sum('total_price'),
        ];
    }

    /**
     * Orders placed today compared with yesterday.
     *
     * @return array{count: int, sum: float, change: ?float}
     */
    public function getOrdersToday(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        $today = DB::table('t_orders')->whereDate('created_at', today());
        $yesterday = DB::table('t_orders')->whereDate('created_at', today()->subDay())->count();
        $count = (clone $today)->count();

        return [
            'count' => $count,
            'sum' => (float) $today->sum('total_price'),
            'change' => $this->change($count, $yesterday),
        ];
    }

    /**
     * Revenue of the last 30 days compared with the 30 days before.
     *
     * @return array{value: float, change: ?float}
     */
    public function getRevenuePeriod(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        $current = (float) $this->paidOrders()->where('created_at', '>=', now()->subDays(self::PERIOD_DAYS))->sum('total_price');

        return ['value' => $current, 'change' => $this->change($current, (float) $this->previousPaidOrders()->sum('total_price'))];
    }

    /**
     * Average paid order of the last 30 days compared with the 30 days before.
     *
     * @return array{value: float, orders: int, change: ?float}
     */
    public function getAverageCheck(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        $current = $this->paidOrders()->where('created_at', '>=', now()->subDays(self::PERIOD_DAYS));
        $orders = (clone $current)->count();
        $value = $orders > 0 ? round((float) $current->sum('total_price') / $orders, 2) : 0.0;

        $previousOrders = $this->previousPaidOrders()->count();
        $previousValue = $previousOrders > 0 ? (float) $this->previousPaidOrders()->sum('total_price') / $previousOrders : 0.0;

        return ['value' => $value, 'orders' => $orders, 'change' => $this->change($value, $previousValue)];
    }

    /**
     * Share of unique visitors of the last 30 days who placed an order.
     * Null without visits: the "Кеширование" module can switch their recording off.
     *
     * @return array{value: ?float, orders: int, visitors: int}
     */
    public function getConversion(): array
    {
        if (! $this->hasTable('t_orders') || ! $this->hasTable('t_page_visits')) {
            return [];
        }

        $since = now()->subDays(self::PERIOD_DAYS);
        $orders = DB::table('t_orders')->where('created_at', '>=', $since)->where('delivery_status', '!=', 'cancelled')->count();
        $visitors = DB::table('t_page_visits')->where('visited_at', '>=', $since)->distinct()->count('ip_address');

        return [
            'value' => $visitors > 0 ? round($orders / $visitors * 100, 2) : null,
            'orders' => $orders,
            'visitors' => $visitors,
        ];
    }

    /**
     * Orders by delivery status, the ones still in work first.
     *
     * @return array<int, array{label: string, value: int, link: string}>
     */
    public function getOrdersByStatus(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        $counts = DB::table('t_orders')
            ->select('delivery_status', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('delivery_status')
            ->pluck('orders_count', 'delivery_status');

        $rows = [];

        foreach (self::DELIVERY_LABELS as $status => $label) {
            if (isset($counts[$status])) {
                $rows[] = ['label' => $label, 'value' => (int) $counts[$status], 'link' => '/orders'];
            }
        }

        return $rows;
    }

    /**
     * Best sellers of the last 30 days. Cancelled and refunded orders do not count.
     *
     * @return array<int, array{label: string, hint: string, value: float, price: true}>
     */
    public function getTopProducts(int $limit = 5): array
    {
        if (! $this->hasTable('t_order_items')) {
            return [];
        }

        return DB::table('t_order_items')
            ->join('t_orders', 't_orders.id', '=', 't_order_items.order_id')
            ->where('t_orders.created_at', '>=', now()->subDays(self::PERIOD_DAYS))
            ->where('t_orders.delivery_status', '!=', 'cancelled')
            ->where('t_orders.payment_status', '!=', 'refunded')
            ->select('t_order_items.product_name')
            ->selectRaw('SUM(t_order_items.amount) as units, SUM(t_order_items.total_price) as sold')
            ->groupBy('t_order_items.product_name')
            ->orderByDesc('sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->product_name,
                'hint' => 'Продано: '.(int) $row->units.' шт.',
                'value' => (float) $row->sold,
                'price' => true,
            ])
            ->all();
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        return DB::table('t_orders')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'name' => $order->name,
                    'email' => $order->email,
                    'phone' => $order->phone,
                    'total_price' => $order->total_price,
                    'payment_status' => $order->payment_status,
                    'delivery_status' => $order->delivery_status,
                    'created_at' => date('d.m.Y H:i', strtotime($order->created_at)),
                ];
            })
            ->toArray();
    }

    /**
     * Get promocodes usage statistics
     */
    public function getPromocodesUsage(int $limit = 10): array
    {
        if (! $this->hasTable('t_promocodes')) {
            return [];
        }

        return DB::table('t_promocodes')
            ->where('current_usage', '>', 0)
            ->orderBy('current_usage', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($promo) {
                return [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'code' => $promo->code,
                    'current_usage' => $promo->current_usage ?? 0,
                    'max_usage' => $promo->max_usage ?? 0,
                    'value' => $promo->value,
                    'type' => $promo->type,
                ];
            })
            ->toArray();
    }

    /**
     * Orders chart of the last 30 days.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function getOrdersChart(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        return $this->dailySeries(DB::table('t_orders'), 'created_at', 'COUNT(*)');
    }

    /**
     * Revenue chart of the last 30 days.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function getRevenueChart(): array
    {
        if (! $this->hasTable('t_orders')) {
            return [];
        }

        return $this->dailySeries($this->paidOrders(), 'created_at', 'SUM(total_price)');
    }

    /**
     * Size of the catalog.
     *
     * @return array{total: int, active: int, categories: int}
     */
    public function getCatalogStats(): array
    {
        if (! $this->hasTable('t_products')) {
            return [];
        }

        return [
            'total' => DB::table('t_products')->count(),
            'active' => DB::table('t_products')->where('is_active', true)->count(),
            'categories' => $this->hasTable('t_catalogs') ? DB::table('t_catalogs')->count() : 0,
        ];
    }

    /**
     * Active products running out. Stock exists only with the CommerceML module.
     *
     * @return array<int, array{label: string, hint: string, value: int, link: string}>
     */
    public function getLowStock(int $limit = 10): array
    {
        if (! $this->hasStockColumn()) {
            return [];
        }

        return DB::table('t_products')
            ->where('is_active', true)
            ->where('quantity', '<=', self::LOW_STOCK_LIMIT)
            ->orderBy('quantity')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'quantity'])
            ->map(fn ($product) => [
                'label' => $product->name,
                'hint' => 'Артикул: '.$product->sku,
                'value' => (int) $product->quantity,
                'link' => "/products/{$product->id}",
            ])
            ->all();
    }

    public function hasStockColumn(): bool
    {
        return $this->hasTable('t_products') && Schema::hasColumn('t_products', 'quantity');
    }

    /**
     * Get subscriptions count
     */
    public function getSubscriptionsCount(): array
    {
        if (! $this->hasTable('t_users_emails')) {
            return [];
        }

        return [
            'total' => DB::table('t_users_emails')->count(),
            'active' => DB::table('t_users_emails')->where('status', 'active')->count(),
            'unsubscribed' => DB::table('t_users_emails')->where('status', 'unsubscribed')->count(),
        ];
    }

    /**
     * Subscriptions chart of the last 30 days.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function getSubscriptionsChart(): array
    {
        if (! $this->hasTable('t_users_emails')) {
            return [];
        }

        return $this->dailySeries(DB::table('t_users_emails'), 'created_at', 'COUNT(*)');
    }

    /**
     * Get reviews count
     */
    public function getReviewsCount(): array
    {
        if (! $this->hasTable('t_comments')) {
            return [];
        }

        return [
            'total' => DB::table('t_comments')->count(),
            'moderated' => DB::table('t_comments')->where('is_moderated', true)->count(),
            'pending' => DB::table('t_comments')->where('is_moderated', false)->count(),
        ];
    }

    /**
     * Get user requests count
     */
    public function getRequestsCount(): array
    {
        if (! $this->hasTable('t_user_requests')) {
            return [];
        }

        return [
            'total' => DB::table('t_user_requests')->count(),
            'today' => DB::table('t_user_requests')->whereDate('created_at', today())->count(),
            'week' => DB::table('t_user_requests')->where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    /**
     * Get recent user requests
     */
    public function getRecentRequests(int $limit = 5): array
    {
        if (! $this->hasTable('t_user_requests')) {
            return [];
        }

        return DB::table('t_user_requests')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'comment' => $request->comment,
                    'created_at' => date('d.m.Y H:i', strtotime($request->created_at)),
                ];
            })
            ->toArray();
    }

    /**
     * Page views today compared with yesterday.
     *
     * @return array{today: int, visitors: int, change: ?float}
     */
    public function getVisitsToday(): array
    {
        if (! $this->hasTable('t_page_visits')) {
            return [];
        }

        $today = DB::table('t_page_visits')->whereDate('visited_at', today());
        $count = (clone $today)->count();
        $yesterday = DB::table('t_page_visits')->whereDate('visited_at', today()->subDay())->count();

        return [
            'today' => $count,
            'visitors' => $today->distinct()->count('ip_address'),
            'change' => $this->change($count, $yesterday),
        ];
    }

    /**
     * Page views chart of the last 30 days.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function getVisitsChart(): array
    {
        if (! $this->hasTable('t_page_visits')) {
            return [];
        }

        return $this->dailySeries(DB::table('t_page_visits'), 'visited_at', 'COUNT(*)');
    }

    /**
     * Get page visits statistics (top 5 pages for last 30 days)
     */
    public function getPageVisitsStats(): array
    {
        if (! $this->hasTable('t_page_visits')) {
            return [];
        }

        // Get top URLs by visits (including all pages, catalogs, products)
        $topUrls = DB::table('t_page_visits')
            ->select('url')
            ->selectRaw('COUNT(*) as visits_count')
            ->where('visited_at', '>=', now()->subDays(self::PERIOD_DAYS))
            ->groupBy('url')
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get();

        $topPages = [];
        foreach ($topUrls as $urlData) {
            $url = $urlData->url;
            $visits = $urlData->visits_count;

            // Extract path from URL
            $path = (string) parse_url($url, PHP_URL_PATH);
            $slug = trim($path, '/');

            // Try to find page title from different sources
            $title = $this->getPageTitleForUrl($slug);

            $topPages[] = [
                'id' => crc32($url), // Generate unique ID from URL
                'title' => $title,
                'slug' => $slug ?: 'главная',
                'visits' => $visits,
            ];
        }

        $totalVisits = DB::table('t_page_visits')
            ->where('visited_at', '>=', now()->subDays(self::PERIOD_DAYS))
            ->count();

        $todayVisits = DB::table('t_page_visits')
            ->whereDate('visited_at', today())
            ->count();

        return [
            'top_pages' => $topPages,
            'total_visits_30d' => $totalVisits,
            'today_visits' => $todayVisits,
        ];
    }

    /**
     * Get page title for URL
     */
    private function getPageTitleForUrl(string $slug): string
    {
        if (empty($slug)) {
            return 'Главная страница';
        }

        // Try to find in pages
        if ($this->hasTable('t_pages')) {
            $title = DB::table('t_pages')->where('slug', $slug)->value('title');
            if ($title) {
                return $title;
            }
        }

        // Try to find in catalogs
        if ($this->hasTable('t_catalogs')) {
            $catalog = DB::table('t_catalogs')->where('slug', $slug)->first();
            if ($catalog) {
                return $catalog->name;
            }
        }

        // Try to find in products (slug might be catalog/product)
        if ($this->hasTable('t_products') && str_contains($slug, '/')) {
            $parts = explode('/', $slug);
            $productSlug = end($parts);
            $product = DB::table('t_products')->where('slug', $productSlug)->first();
            if ($product) {
                return $product->name;
            }
        }

        // Fallback: format slug as title
        return ucfirst(str_replace(['-', '_', '/'], ' ', $slug));
    }

    /**
     * One value per day for the whole period, days without rows included.
     * A single grouped query instead of one query per day.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function dailySeries(Builder $query, string $dateColumn, string $aggregate): array
    {
        $start = today()->subDays(self::PERIOD_DAYS - 1);

        $values = $query
            ->where($dateColumn, '>=', $start)
            ->selectRaw("DATE({$dateColumn}) as day, {$aggregate} as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];

        for ($i = 0; $i < self::PERIOD_DAYS; $i++) {
            $date = $start->copy()->addDays($i);
            $series[] = [
                'label' => $date->format('d.m'),
                'value' => (float) ($values[$date->format('Y-m-d')] ?? 0),
            ];
        }

        return $series;
    }

    private function paidOrders(): Builder
    {
        return DB::table('t_orders')->where('payment_status', 'paid');
    }

    /**
     * Paid orders of the 30 days before the current period.
     */
    private function previousPaidOrders(): Builder
    {
        return $this->paidOrders()->whereBetween('created_at', [
            now()->subDays(self::PERIOD_DAYS * 2),
            now()->subDays(self::PERIOD_DAYS),
        ]);
    }

    /**
     * Change in percent. Null when there is nothing to compare with.
     */
    private function change(float $current, float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    private function hasTable(string $table): bool
    {
        return app(SiteCacheService::class)->hasTable($table);
    }
}
