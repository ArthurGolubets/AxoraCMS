<?php

namespace HolartWeb\AxoraCMS\Http\Middleware;

use Closure;
use HolartWeb\AxoraCMS\Services\PageVisitService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisits
{
    protected PageVisitService $pageVisitService;

    public function __construct(PageVisitService $pageVisitService)
    {
        $this->pageVisitService = $pageVisitService;
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

        try {
            $this->pageVisitService->track(
                url: $request->fullUrl(),
                routeName: $request->route()?->getName(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                referer: $request->header('referer')
            );
        } catch (\Exception $e) {
            // Silently fail to not break the application
            logger()->error('Failed to track page visit: '.$e->getMessage());
        }

        return $response;
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
