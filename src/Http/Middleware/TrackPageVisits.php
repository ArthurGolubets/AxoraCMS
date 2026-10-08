<?php

namespace HolartWeb\AxoraCMS\Http\Middleware;

use Closure;
use HolartWeb\AxoraCMS\Services\PageVisitService;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisits
{
    protected PageVisitService $pageVisitService;

    protected SiteCacheService $siteCache;

    public function __construct(PageVisitService $pageVisitService, SiteCacheService $siteCache)
    {
        $this->pageVisitService = $pageVisitService;
        $this->siteCache = $siteCache;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip tracking for admin routes, API routes, and AJAX requests
        if ($this->shouldSkipTracking($request, $response)) {
            return $response;
        }

        $this->trackVisit($request);

        return $response;
    }

    /**
     * Record a visit of an already-checked page. The "Кеширование" module decides when:
     * right away (default), after the response is sent to the browser, or never.
     */
    public function trackVisit(Request $request): void
    {
        $mode = $this->siteCache->visitsMode();

        if ($mode === SiteCacheService::VISITS_OFF) {
            return;
        }

        $visit = [
            'url' => $request->fullUrl(),
            'routeName' => $request->route()?->getName(),
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'referer' => $request->header('referer'),
        ];

        $record = function () use ($visit): void {
            try {
                $this->pageVisitService->track(...$visit);
            } catch (\Exception $e) {
                // Silently fail to not break the application
                logger()->error('Failed to track page visit: '.$e->getMessage());
            }
        };

        if ($mode === SiteCacheService::VISITS_DEFERRED) {
            app()->terminating($record);

            return;
        }

        $record();
    }

    /**
     * Determine if tracking should be skipped
     */
    private function shouldSkipTracking(Request $request, Response $response): bool
    {
        // Only track GET requests
        if (! $request->isMethod('GET')) {
            return true;
        }

        // Only track successful responses
        if ($response->getStatusCode() !== 200) {
            return true;
        }

        // Exclude AJAX and JSON requests
        if ($request->ajax() || $request->expectsJson()) {
            return true;
        }

        // Exclude admin panel routes
        if ($request->is('admin') || $request->is('admin/*')) {
            return true;
        }

        // Exclude API routes
        if ($request->is('api/*')) {
            return true;
        }

        return false;
    }
}
