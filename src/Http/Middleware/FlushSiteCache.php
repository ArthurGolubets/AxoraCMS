<?php

namespace HolartWeb\AxoraCMS\Http\Middleware;

use Closure;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Кеширование" module: drops cached site content after anything changes data —
 * every successful write request of the admin panel API and every 1C import step.
 * Model events alone are not enough: bulk updates (mass move/delete, price manager,
 * imports) go through the query builder and fire none.
 */
class FlushSiteCache
{
    /**
     * CommerceML exchange modes that write catalog data.
     */
    private const EXCHANGE_WRITE_MODES = ['import', 'deactivate', 'complete'];

    public function __construct(protected SiteCacheService $siteCache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400 && $this->siteCache->isInstalled() && $this->changesData($request)) {
            $this->siteCache->flush(SiteCacheService::CONTENT_GROUPS);
        }

        return $response;
    }

    protected function changesData(Request $request): bool
    {
        if ($request->is('api/1c/*')) {
            return in_array($request->query('mode'), self::EXCHANGE_WRITE_MODES, true);
        }

        // Public API writes (site form submissions) never change cached content.
        if ($request->is('api/*')) {
            return false;
        }

        return ! $request->isMethodSafe();
    }
}
