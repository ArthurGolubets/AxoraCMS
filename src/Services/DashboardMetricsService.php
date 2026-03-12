<?php

namespace HolartWeb\HolartCMS\Services;

use HolartWeb\HolartCMS\Models\TAdministrator;
use HolartWeb\HolartCMS\Models\TAdminAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    /**
     * Get users statistics
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
        if (!Schema::hasTable('t_admin_actions')) {
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
        if (!Schema::hasTable('t_orders')) {
            return [];
        }

        try {
            return [
                'total' => DB::table('t_orders')->count(),
                'new' => DB::table('t_orders')->where('delivery_status', 'pending')->count(),
                'revenue' => (float) DB::table('t_orders')->sum('total_price'),
                'pending_payment' => (float) DB::table('t_orders')->where('payment_status', 'pending')->sum('total_price'),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        if (!Schema::hasTable('t_orders')) {
            return [];
        }

        try {
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
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get promocodes usage statistics
     */
    public function getPromocodesUsage(int $limit = 10): array
    {
        if (!Schema::hasTable('t_promocodes')) {
            return [];
        }

        try {
            return DB::table('t_promocodes')
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
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get subscriptions count
     */
    public function getSubscriptionsCount(): array
    {
        if (!Schema::hasTable('t_users_emails')) {
            return [];
        }

        try {
            return [
                'total' => DB::table('t_users_emails')->count(),
                'active' => DB::table('t_users_emails')->where('status', 'active')->count(),
                'unsubscribed' => DB::table('t_users_emails')->where('status', 'unsubscribed')->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get page visits statistics (top 5 pages for last 30 days)
     */
    public function getPageVisitsStats(): array
    {
        if (!Schema::hasTable('t_page_visits')) {
            return [];
        }

        try {
            // Get top URLs by visits (including all pages, catalogs, products)
            $topUrls = DB::table('t_page_visits')
                ->select('url')
                ->selectRaw('COUNT(*) as visits_count')
                ->where('visited_at', '>=', now()->subDays(30))
                ->groupBy('url')
                ->orderByDesc('visits_count')
                ->limit(5)
                ->get();

            $topPages = [];
            foreach ($topUrls as $urlData) {
                $url = $urlData->url;
                $visits = $urlData->visits_count;

                // Extract path from URL
                $path = parse_url($url, PHP_URL_PATH);
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
                ->where('visited_at', '>=', now()->subDays(30))
                ->count();

            $todayVisits = DB::table('t_page_visits')
                ->whereDate('visited_at', today())
                ->count();

            return [
                'top_pages' => $topPages,
                'total_visits_30d' => $totalVisits,
                'today_visits' => $todayVisits,
            ];
        } catch (\Exception $e) {
            return [];
        }
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
        if (Schema::hasTable('t_pages')) {
            $pageModel = $this->getPageModel();
            if ($pageModel) {
                $page = $pageModel::where('slug', $slug)->first();
                if ($page) {
                    return $page->title;
                }
            }
        }

        // Try to find in catalogs
        if (Schema::hasTable('t_catalogs')) {
            $catalog = DB::table('t_catalogs')->where('slug', $slug)->first();
            if ($catalog) {
                return $catalog->name;
            }
        }

        // Try to find in products (slug might be catalog/product)
        if (Schema::hasTable('t_products') && str_contains($slug, '/')) {
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
     * Get Page model class
     */
    private function getPageModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\SEO\TPage')) {
            return 'HolartWeb\HolartCMS\Models\SEO\TPage';
        }

        return null;
    }

    /**
     * Get subscriptions chart data (last 30 days)
     */
    public function getSubscriptionsChart(int $days = 30): array
    {
        if (!Schema::hasTable('t_users_emails')) {
            return [];
        }

        try {
            $data = [];
            $startDate = now()->subDays($days - 1); // Include today

            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $count = DB::table('t_users_emails')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->count();

                $data[] = [
                    'label' => $date->format('d.m'),
                    'value' => $count,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get reviews count
     */
    public function getReviewsCount(): array
    {
        if (!Schema::hasTable('t_comments')) {
            return [];
        }

        try {
            return [
                'total' => DB::table('t_comments')->count(),
                'moderated' => DB::table('t_comments')->where('is_moderated', true)->count(),
                'pending' => DB::table('t_comments')->where('is_moderated', false)->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get user requests count
     */
    public function getRequestsCount(): array
    {
        if (!Schema::hasTable('t_user_requests')) {
            return [];
        }

        try {
            return [
                'total' => DB::table('t_user_requests')->count(),
                'today' => DB::table('t_user_requests')->whereDate('created_at', today())->count(),
                'week' => DB::table('t_user_requests')->where('created_at', '>=', now()->subWeek())->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent user requests
     */
    public function getRecentRequests(int $limit = 5): array
    {
        if (!Schema::hasTable('t_user_requests')) {
            return [];
        }

        try {
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
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get orders chart data (30 days)
     */
    public function getOrdersChart(int $days = 30): array
    {
        if (!Schema::hasTable('t_orders')) {
            return [];
        }

        try {
            $data = [];
            $startDate = now()->subDays($days - 1); // Include today

            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $count = DB::table('t_orders')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->count();

                $data[] = [
                    'label' => $date->format('d.m'),
                    'value' => $count,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get revenue chart data (30 days)
     */
    public function getRevenueChart(int $days = 30): array
    {
        if (!Schema::hasTable('t_orders')) {
            return [];
        }

        try {
            $data = [];
            $startDate = now()->subDays($days - 1); // Include today

            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $revenue = (float) DB::table('t_orders')
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total_price');

                $data[] = [
                    'label' => $date->format('d.m'),
                    'value' => $revenue,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }


    /**
     * Get all metrics for dashboard
     */
    public function getAllMetrics(): array
    {
        $metrics = [
            'users_stats' => $this->getUsersStats(),
            'recent_logs' => $this->getRecentLogs(),
        ];

        // Add commerce metrics if module is installed
        if (Schema::hasTable('t_orders')) {
            $metrics['orders_stats'] = $this->getOrdersStats();
            $metrics['recent_orders'] = $this->getRecentOrders();
            $metrics['promocodes_usage'] = $this->getPromocodesUsage();
            $metrics['orders_chart'] = $this->getOrdersChart();
            $metrics['revenue_chart'] = $this->getRevenueChart();
        }

        // Add callback metrics if module is installed
        if (Schema::hasTable('t_users_emails')) {
            $metrics['subscriptions_count'] = $this->getSubscriptionsCount();
            $metrics['subscriptions_chart'] = $this->getSubscriptionsChart();
            $metrics['reviews_count'] = $this->getReviewsCount();
            $metrics['requests_count'] = $this->getRequestsCount();
            $metrics['recent_requests'] = $this->getRecentRequests();
        }

        // Add page visits stats if SEO module is installed
        if (Schema::hasTable('t_pages') && Schema::hasTable('t_page_visits')) {
            $metrics['page_visits_stats'] = $this->getPageVisitsStats();
        }

        return $metrics;
    }

    /**
     * Get metric by type
     */
    public function getMetricByType(string $type): mixed
    {
        return match ($type) {
            'users_stats' => $this->getUsersStats(),
            'recent_logs' => $this->getRecentLogs(),
            'orders_stats' => $this->getOrdersStats(),
            'recent_orders' => $this->getRecentOrders(),
            'promocodes_usage' => $this->getPromocodesUsage(),
            'orders_chart' => $this->getOrdersChart(),
            'revenue_chart' => $this->getRevenueChart(),
            'subscriptions_count' => $this->getSubscriptionsCount(),
            'subscriptions_chart' => $this->getSubscriptionsChart(),
            'reviews_count' => $this->getReviewsCount(),
            'requests_count' => $this->getRequestsCount(),
            'recent_requests' => $this->getRecentRequests(),
            default => [],
        };
    }
}
