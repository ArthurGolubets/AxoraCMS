<?php

namespace HolartWeb\AxoraCMS\Http\Middleware;

use Closure;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * "Кеширование" module, full-page cache: serves the ready HTML of public pages to
 * guests without running the controller, views or any database query.
 *
 * Only plain GET pages for anonymous visitors with an empty cart/favorites and no
 * flashed messages are cached. The CSRF token is swapped for the visitor's own on
 * every hit, so forms on cached pages keep working. A page can opt out by sending
 * the "X-Axora-No-Cache" response header.
 */
class CacheFullPage
{
    public const HEADER = 'X-Axora-Cache';

    public const OPT_OUT_HEADER = 'X-Axora-No-Cache';

    private const CSRF_PLACEHOLDER = '__AXORA_CSRF_TOKEN__';

    private const MAX_BYTES = 3 * 1024 * 1024;

    /**
     * Session keys that make a page personal (cart, favorites).
     */
    private const PERSONAL_SESSION_KEYS = ['holart_cart', 'holart_favorites'];

    public function __construct(protected SiteCacheService $siteCache) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! $this->isCacheable($request)) {
            return $next($request);
        }

        $key = $this->cacheKey($request);
        $cached = $this->siteCache->get(SiteCacheService::GROUP_HTML, $key);

        if (is_array($cached) && isset($cached['content'])) {
            app(TrackPageVisits::class)->trackVisit($request);

            return $this->hit($cached);
        }

        $response = $next($request);

        if ($this->isStorable($response)) {
            $this->siteCache->put(SiteCacheService::GROUP_HTML, $key, [
                'content' => str_replace(csrf_token(), self::CSRF_PLACEHOLDER, (string) $response->getContent()),
                'content_type' => $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
            ], (int) $this->siteCache->setting('cache_html_ttl'));

            $response->headers->set(self::HEADER, 'MISS');
        }

        return $response;
    }

    protected function isCacheable(Request $request): bool
    {
        if (! $this->siteCache->enabled(SiteCacheService::GROUP_HTML)) {
            return false;
        }

        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($request->is('api/*') || $this->isExcluded($request)) {
            return false;
        }

        if (! $this->siteCache->setting('cache_html_query') && $request->getQueryString()) {
            return false;
        }

        try {
            if (Auth::guard('admin')->check() || Auth::check()) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        if ($session->has('errors') || ! empty($session->get('_flash.old', []))) {
            return false;
        }

        foreach (self::PERSONAL_SESSION_KEYS as $sessionKey) {
            if (! empty($session->get($sessionKey))) {
                return false;
            }
        }

        return true;
    }

    protected function isExcluded(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $patterns = preg_split('/\R/', (string) $this->siteCache->setting('cache_html_excluded')) ?: [];

        foreach ($patterns as $pattern) {
            $pattern = trim(trim($pattern), '/');
            if ($pattern !== '' && Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isStorable(SymfonyResponse $response): bool
    {
        if (! $response instanceof Response || $response->getStatusCode() !== 200) {
            return false;
        }

        if ($response->headers->has(self::OPT_OUT_HEADER)) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', 'text/html');

        return str_contains($contentType, 'text/html') && strlen((string) $response->getContent()) <= self::MAX_BYTES;
    }

    protected function cacheKey(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        return $request->getHost().'/'.trim($request->path(), '/').'?'.http_build_query($query);
    }

    /**
     * @param  array{content: string, content_type?: string}  $cached
     */
    protected function hit(array $cached): Response
    {
        $content = str_replace(self::CSRF_PLACEHOLDER, csrf_token(), $cached['content']);

        return new Response($content, 200, [
            'Content-Type' => $cached['content_type'] ?? 'text/html; charset=UTF-8',
            self::HEADER => 'HIT',
        ]);
    }
}
