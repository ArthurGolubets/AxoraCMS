<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * Settings page of the "Кеширование" module.
 */
class CacheController extends Controller
{
    public function __construct(protected SiteCacheService $siteCache) {}

    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function update(Request $request): JsonResponse
    {
        $storeNames = array_column($this->siteCache->availableStores(), 'name');

        $validated = $request->validate([
            'cache_enabled' => ['required', 'boolean'],
            'cache_schema' => ['required', 'boolean'],
            'cache_settings' => ['required', 'boolean'],
            'cache_pages' => ['required', 'boolean'],
            'cache_catalog' => ['required', 'boolean'],
            'cache_infoblocks' => ['required', 'boolean'],
            'cache_html' => ['required', 'boolean'],
            'cache_html_query' => ['required', 'boolean'],
            'cache_html_excluded' => ['nullable', 'string', 'max:5000'],
            'cache_ttl' => ['required', 'integer', 'min:1', 'max:10080'],
            'cache_html_ttl' => ['required', 'integer', 'min:1', 'max:1440'],
            'cache_store' => ['required', 'string', Rule::in($storeNames)],
            'cache_visits_mode' => ['required', Rule::in([
                SiteCacheService::VISITS_SYNC,
                SiteCacheService::VISITS_DEFERRED,
                SiteCacheService::VISITS_OFF,
            ])],
        ]);

        $validated['cache_html_excluded'] = (string) ($validated['cache_html_excluded'] ?? '');

        $this->siteCache->saveSettings($validated);

        TAdminAction::log('updated', 'settings', null, 'Изменены настройки модуля «Кеширование»');

        return response()->json([
            'message' => 'Настройки кеширования сохранены',
            ...$this->payload(),
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->siteCache->flush();

        TAdminAction::log('updated', 'settings', null, 'Очищен кеш сайта');

        return response()->json(['message' => 'Кеш сайта очищен']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'settings' => ['cache_store' => $this->siteCache->storeName()] + $this->siteCache->settings(),
            'store' => $this->siteCache->storeName(),
            'stores' => $this->siteCache->availableStores(),
            'default_html_excluded' => SiteCacheService::DEFAULT_HTML_EXCLUDED,
        ];
    }
}
