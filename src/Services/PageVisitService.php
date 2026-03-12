<?php

namespace HolartWeb\HolartCMS\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class PageVisitService
{
    /**
     * Track a page visit
     */
    public function track(
        string $url,
        ?string $routeName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $referer = null
    ): void {
        // Check if tables exist
        if (!Schema::hasTable('t_pages') || !Schema::hasTable('t_page_visits')) {
            return;
        }

        $pageModel = $this->getPageModel();
        $pageVisitModel = $this->getPageVisitModel();

        if (!$pageModel || !$pageVisitModel) {
            return;
        }

        // Try to find corresponding page
        $page = null;

        if ($routeName) {
            $page = $pageModel::where('route_name', $routeName)->first();
        }

        // If no page found by route, try to find by slug from URL
        if (!$page) {
            $slug = $this->extractSlugFromUrl($url);
            if ($slug) {
                $page = $pageModel::where('slug', $slug)->first();
            }
        }

        // Record visit
        $pageVisitModel::record(
            $page?->id,
            $url,
            $routeName,
            $ipAddress,
            $userAgent,
            $referer
        );

        // Increment page views count cache
        if ($page) {
            $page->incrementViews();
        }
    }

    /**
     * Extract slug from URL
     */
    private function extractSlugFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path || $path === '/') {
            return null;
        }

        return trim($path, '/');
    }

    /**
     * Get popular pages
     */
    public function getPopularPages(int $limit = 10, string $period = '30days'): Collection
    {
        $pageModel = $this->getPageModel();

        if (!$pageModel) {
            return collect();
        }

        $date = $this->getPeriodStartDate($period);

        return $pageModel::select('t_pages.*')
            ->selectRaw('COUNT(t_page_visits.id) as visits')
            ->leftJoin('t_page_visits', 't_pages.id', '=', 't_page_visits.page_id')
            ->where(function ($query) use ($date) {
                $query->where('t_page_visits.visited_at', '>=', $date)
                    ->orWhereNull('t_page_visits.visited_at');
            })
            ->groupBy('t_pages.id')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();
    }

    /**
     * Get total visits count
     */
    public function getTotalVisits(string $period = 'all'): int
    {
        $pageVisitModel = $this->getPageVisitModel();

        if (!$pageVisitModel) {
            return 0;
        }

        $query = $pageVisitModel::query();

        if ($period !== 'all') {
            $date = $this->getPeriodStartDate($period);
            $query->where('visited_at', '>=', $date);
        }

        return $query->count();
    }

    /**
     * Get visits chart data
     */
    public function getVisitsChart(string $period = '7days'): array
    {
        $pageVisitModel = $this->getPageVisitModel();

        if (!$pageVisitModel) {
            return ['labels' => [], 'data' => []];
        }

        $date = $this->getPeriodStartDate($period);
        $groupFormat = $this->getGroupFormat($period);

        $visits = $pageVisitModel::select(
            DB::raw("DATE_FORMAT(visited_at, '{$groupFormat}') as date"),
            DB::raw('COUNT(*) as count')
        )
            ->where('visited_at', '>=', $date)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $visits->pluck('date')->toArray(),
            'data' => $visits->pluck('count')->toArray(),
        ];
    }

    /**
     * Get page visits statistics
     */
    public function getPageStatistics(int $pageId): array
    {
        $pageModel = $this->getPageModel();

        if (!$pageModel) {
            return [];
        }

        $page = $pageModel::findOrFail($pageId);

        return [
            'total_visits' => $page->views_count,
            'today' => $page->getViewsForPeriod('today'),
            'week' => $page->getViewsForPeriod('7days'),
            'month' => $page->getViewsForPeriod('30days'),
            'year' => $page->getViewsForPeriod('year'),
        ];
    }

    /**
     * Get unlinked visits (visits without page_id)
     */
    public function getUnlinkedVisits(int $limit = 20): Collection
    {
        $pageVisitModel = $this->getPageVisitModel();

        if (!$pageVisitModel) {
            return collect();
        }

        return $pageVisitModel::select('url', 'route_name', DB::raw('COUNT(*) as count'))
            ->whereNull('page_id')
            ->groupBy('url', 'route_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->subDays(7),
        };
    }

    /**
     * Get SQL date format for grouping
     */
    private function getGroupFormat(string $period): string
    {
        return match ($period) {
            'today' => '%H:00',
            '7days' => '%Y-%m-%d',
            '30days' => '%Y-%m-%d',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    /**
     * Get Page model class
     *
     * @return string|null
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
     * Get PageVisit model class
     *
     * @return string|null
     */
    private function getPageVisitModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\SEO\TPageVisit')) {
            return 'HolartWeb\HolartCMS\Models\SEO\TPageVisit';
        }

        return null;
    }
}
