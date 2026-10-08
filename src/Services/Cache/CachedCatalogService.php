<?php

namespace HolartWeb\AxoraCMS\Services\Cache;

use Closure;
use HolartWeb\AxoraCMS\Services\CatalogService;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * CatalogService whose read methods are served from the "Кеширование" module cache
 * (group "catalog"). Bound in the container instead of CatalogService — when the
 * module is off every call goes straight to the parent. Search and random
 * selections are intentionally not cached.
 */
class CachedCatalogService extends CatalogService
{
    public function getCatalogTree(bool $activeOnly = false): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogTree($activeOnly));
    }

    public function getCatalogWithChildren(int $catalogId, bool $activeOnly = false): ?array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogWithChildren($catalogId, $activeOnly));
    }

    public function getCatalogProducts(int $catalogId, ?int $limit = null, int $page = 1, bool $activeOnly = false)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogProducts($catalogId, $limit, $page, $activeOnly));
    }

    public function getCatalogsByCharacteristics(array $characteristics, ?int $limit = null, int $page = 1, bool $activeOnly = false)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogsByCharacteristics($characteristics, $limit, $page, $activeOnly));
    }

    public function getProductsByCharacteristics(array $characteristics, ?int $catalogId = null, ?int $limit = null, int $page = 1, bool $activeOnly = false)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductsByCharacteristics($characteristics, $catalogId, $limit, $page, $activeOnly));
    }

    public function getProductVariants(int $productId): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductVariants($productId));
    }

    public function getCatalogsWithFilters(array $filters = [], ?int $limit = null, int $page = 1, array $order = ['name' => 'asc'])
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogsWithFilters($filters, $limit, $page, $order));
    }

    public function getProductsWithFilters(array $filters = [], ?int $catalogId = null, ?int $limit = null, int $page = 1, array $order = ['name' => 'asc'])
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductsWithFilters($filters, $catalogId, $limit, $page, $order));
    }

    public function getCatalogBySlug(string $slug): ?array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogBySlug($slug));
    }

    public function getProductBySlug(string $slug, bool $withVariants = true): ?array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductBySlug($slug, $withVariants));
    }

    public function getCatalogBreadcrumbs(?int $catalogId = null): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogBreadcrumbs($catalogId));
    }

    public function getProductBreadcrumbs(int $productId): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductBreadcrumbs($productId));
    }

    public function getRelatedProducts(int $productId, int $limit = 6, bool $activeOnly = true): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getRelatedProducts($productId, $limit, $activeOnly));
    }

    public function getCompanionProducts(int $productId, int|string|null $variant = null, bool $activeOnly = true): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCompanionProducts($productId, $variant, $activeOnly));
    }

    public function getFeaturedProducts(string $type, ?int $catalogId = null, ?int $limit = null, int $page = 1, bool $activeOnly = true)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getFeaturedProducts($type, $catalogId, $limit, $page, $activeOnly));
    }

    public function getAvailableProductCharacteristics(?int $catalogId = null): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getAvailableProductCharacteristics($catalogId));
    }

    public function getAvailableCatalogCharacteristics(): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getAvailableCatalogCharacteristics());
    }

    public function getCatalogProductCount(int $catalogId, bool $activeOnly = false): int
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogProductCount($catalogId, $activeOnly));
    }

    public function getCatalogPriceRange(int $catalogId, bool $activeOnly = false): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogPriceRange($catalogId, $activeOnly));
    }

    public function searchProductsByProperties(array $properties, ?int $catalogId = null, ?int $limit = null, int $page = 1, bool $activeOnly = false)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::searchProductsByProperties($properties, $catalogId, $limit, $page, $activeOnly));
    }

    public function getCatalogProperties(int $catalogId): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogProperties($catalogId));
    }

    public function getProductsByCatalogRecursive(int $catalogId, array $filters = [], array $order = ['name' => 'asc'], ?int $limit = null, int $page = 1, bool $activeOnly = true)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getProductsByCatalogRecursive($catalogId, $filters, $order, $limit, $page, $activeOnly));
    }

    public function getCatalogByCode(string $code): ?array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getCatalogByCode($code));
    }

    public function getAllCatalogs(array $filters = [], array $order = ['name' => 'asc'], bool $activeOnly = true): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getAllCatalogs($filters, $order, $activeOnly));
    }

    public function getRootCatalogs(bool $activeOnly = true): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getRootCatalogs($activeOnly));
    }

    public function getChildCatalogs(int $parentId, bool $activeOnly = true): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getChildCatalogs($parentId, $activeOnly));
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    protected function cached(string $method, array $arguments, Closure $callback): mixed
    {
        try {
            $key = $method.'|'.serialize($arguments);
        } catch (Throwable) {
            return $callback();
        }

        return app(SiteCacheService::class)->remember(SiteCacheService::GROUP_CATALOG, $key, $callback);
    }
}
