<?php

namespace HolartWeb\AxoraCMS\Services\Cache;

use Closure;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockSection;
use HolartWeb\AxoraCMS\Services\InfoBlockService;
use HolartWeb\AxoraCMS\Services\SiteCacheService;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * InfoBlockService whose read methods are served from the "Кеширование" module cache
 * (group "infoblocks"). Bound in the container instead of InfoBlockService — when the
 * module is off every call goes straight to the parent. Search and random
 * selections are intentionally not cached.
 */
class CachedInfoBlockService extends InfoBlockService
{
    public function getInfoBlockByCode(string $code): ?TInfoBlock
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getInfoBlockByCode($code));
    }

    public function getElements(string $code, array $filter = [], array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElements($code, $filter, $order, $perPage, $page));
    }

    public function getActiveElements(string $code, array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getActiveElements($code, $order, $perPage, $page));
    }

    public function getElementById(string $code, int $id): ?TInfoBlockElement
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementById($code, $id));
    }

    public function getElementByCode(string $infoBlockCode, string $elementCode): ?TInfoBlockElement
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementByCode($infoBlockCode, $elementCode));
    }

    public function getFirstElement(string $code, array $filter = [], array $order = ['sort' => 'asc']): ?TInfoBlockElement
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getFirstElement($code, $filter, $order));
    }

    public function countElements(string $code, array $filter = []): int
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::countElements($code, $filter));
    }

    public function getElementsByProperty(string $code, string $propertyCode, $value, array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementsByProperty($code, $propertyCode, $value, $order, $perPage, $page));
    }

    public function getElementsGroupedByProperty(string $code, string $propertyCode, array $filter = []): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementsGroupedByProperty($code, $propertyCode, $filter));
    }

    public function elementExists(string $code, int $id): bool
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::elementExists($code, $id));
    }

    public function elementExistsByCode(string $infoBlockCode, string $elementCode): bool
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::elementExistsByCode($infoBlockCode, $elementCode));
    }

    public function getElementWithFields(string $code, int $id): ?array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementWithFields($code, $id));
    }

    public function getLatestElements(string $code, int $count = 10, array $filter = []): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getLatestElements($code, $count, $filter));
    }

    public function getPopularElements(string $code, string $metricProperty = 'views', int $count = 10, array $filter = []): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getPopularElements($code, $metricProperty, $count, $filter));
    }

    public function getBreadcrumbs(string $infoBlockCode, ?int $elementId = null): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getBreadcrumbs($infoBlockCode, $elementId));
    }

    public function getEnumOptions(string $code): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getEnumOptions($code));
    }

    public function getSections(string $code, bool $activeOnly = true): Collection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getSections($code, $activeOnly));
    }

    public function getSectionsTree(string $code, bool $activeOnly = true): array
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getSectionsTree($code, $activeOnly));
    }

    public function getElementsBySection(string $code, int $sectionId, array $filter = [], array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1, bool $activeOnly = true)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementsBySection($code, $sectionId, $filter, $order, $perPage, $page, $activeOnly));
    }

    public function getElementsBySectionRecursive(string $code, int $sectionId, array $filter = [], array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1, bool $activeOnly = true)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getElementsBySectionRecursive($code, $sectionId, $filter, $order, $perPage, $page, $activeOnly));
    }

    public function getAllCatalogElements(string $code, array $filter = [], array $order = ['sort' => 'asc'], ?int $perPage = null, int $page = 1, bool $activeOnly = true)
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getAllCatalogElements($code, $filter, $order, $perPage, $page, $activeOnly));
    }

    public function getSectionById(string $code, int $sectionId): ?TInfoBlockSection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getSectionById($code, $sectionId));
    }

    public function getSectionByCode(string $infoBlockCode, string $sectionCode): ?TInfoBlockSection
    {
        return $this->cached(__FUNCTION__, func_get_args(), fn () => parent::getSectionByCode($infoBlockCode, $sectionCode));
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

        return app(SiteCacheService::class)->remember(SiteCacheService::GROUP_INFOBLOCKS, $key, $callback);
    }
}
