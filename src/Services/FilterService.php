<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Shop\TFilter;
use Illuminate\Support\Collection;

class FilterService
{
    /**
     * Get catalog IDs including children catalogs
     */
    private function getCatalogIdsWithChildren($catalogId): array
    {
        if ($catalogId === null) {
            return [];
        }

        if (!class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return [$catalogId];
        }

        $catalog = \HolartWeb\AxoraCMS\Models\Shop\TCatalog::find($catalogId);
        if (!$catalog) {
            return [$catalogId];
        }

        $catalogIds = [$catalogId];
        $this->collectChildrenIds($catalog, $catalogIds);

        return $catalogIds;
    }

    /**
     * Recursively collect all children catalog IDs
     */
    private function collectChildrenIds($catalog, &$catalogIds): void
    {
        $children = $catalog->children;
        foreach ($children as $child) {
            $catalogIds[] = $child->id;
            $this->collectChildrenIds($child, $catalogIds);
        }
    }

    /**
     * Get filters for a catalog with their values and product counts
     * If catalogId is null, returns filters for all products
     */
    public function getFiltersForCatalog($catalogId = null, array $selectedFilters = []): array
    {
        $query = TFilter::with('activeValues')
            ->active()
            ->orderBy('sort')
            ->orderBy('name');

        if ($catalogId !== null) {
            $query->forCatalog($catalogId);
        }

        $filters = $query->get();

        // Add product counts for each filter value
        $filtersWithCounts = $filters->map(function ($filter) use ($catalogId, $selectedFilters) {
            $values = $filter->activeValues->map(function ($value) use ($catalogId, $filter, $selectedFilters) {
                // Count products with this filter value in this catalog
                $count = $this->countProductsWithFilter($catalogId, $value->id, $selectedFilters, $filter->id);

                return [
                    'id' => $value->id,
                    'value' => $value->value,
                    'code' => $value->code,
                    'count' => $count,
                    'is_selected' => in_array($value->id, $selectedFilters[$filter->id] ?? []),
                ];
            });

            return [
                'id' => $filter->id,
                'name' => $filter->name,
                'code' => $filter->code,
                'type' => $filter->type,
                'values' => $values,
            ];
        });

        // Add price filter
        $priceFilter = $this->getPriceFilter($catalogId, $selectedFilters);
        if ($priceFilter) {
            $filtersWithCounts->push($priceFilter);
        }

        return $filtersWithCounts->toArray();
    }

    /**
     * Generate filter for all products or specific catalog
     */
    public function generateFilter($catalogId = null, array $selectedFilters = []): array
    {
        return $this->getFiltersForCatalog($catalogId, $selectedFilters);
    }

    /**
     * Count products in catalog with specific filter value
     * If catalogId is null, counts products across all catalogs
     */
    private function countProductsWithFilter($catalogId, $filterValueId, array $selectedFilters, $currentFilterId): int
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            return 0;
        }

        $query = \HolartWeb\AxoraCMS\Models\Shop\TProduct::query();

        // Apply catalog filter (including children) only if catalogId is provided
        if ($catalogId !== null) {
            $catalogIds = $this->getCatalogIdsWithChildren($catalogId);
            $query->whereIn('catalog_id', $catalogIds);
        }

        // Apply selected filters (excluding current filter to show available options)
        foreach ($selectedFilters as $filterId => $valueIds) {
            if ($filterId == $currentFilterId) {
                continue;
            }

            if (!empty($valueIds)) {
                $query->whereHas('filterValues', function ($q) use ($valueIds) {
                    $q->whereIn('t_filter_values.id', $valueIds);
                });
            }
        }

        // Count products with this specific filter value
        $query->whereHas('filterValues', function ($q) use ($filterValueId) {
            $q->where('t_filter_values.id', $filterValueId);
        });

        return $query->count();
    }

    /**
     * Filter products by selected filters
     * If catalogId is null, filters products across all catalogs
     */
    public function filterProducts($catalogId = null, array $selectedFilters = [], $query = null)
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            return collect([]);
        }

        if ($query === null) {
            $query = \HolartWeb\AxoraCMS\Models\Shop\TProduct::query();

            // Apply catalog filter (including children) only if catalogId is provided
            if ($catalogId !== null) {
                $catalogIds = $this->getCatalogIdsWithChildren($catalogId);
                $query->whereIn('catalog_id', $catalogIds);
            }
        }

        // Apply each filter
        foreach ($selectedFilters as $filterId => $valueIds) {
            if (empty($valueIds)) {
                continue;
            }

            // Handle price filter separately
            if ($filterId === 'price') {
                if (isset($valueIds['min'])) {
                    $query->where('price', '>=', $valueIds['min']);
                }
                if (isset($valueIds['max'])) {
                    $query->where('price', '<=', $valueIds['max']);
                }
                continue;
            }

            $query->whereHas('filterValues', function ($q) use ($filterId, $valueIds) {
                $q->where('filter_id', $filterId)
                  ->whereIn('t_filter_values.id', $valueIds);
            });
        }

        return $query;
    }

    /**
     * Get products filtered by selected filters (for catalog page without catalogId)
     */
    public function getFilteredProducts(array $selectedFilters = [], $catalogId = null)
    {
        $query = $this->filterProducts($catalogId, $selectedFilters);
        return $query->where('is_active', true)->get();
    }

    /**
     * Get applied filters info
     */
    public function getAppliedFiltersInfo(array $selectedFilters): array
    {
        $appliedFilters = [];

        foreach ($selectedFilters as $filterId => $valueIds) {
            if (empty($valueIds)) {
                continue;
            }

            $filter = TFilter::with(['values' => function ($query) use ($valueIds) {
                $query->whereIn('id', $valueIds);
            }])->find($filterId);

            if ($filter) {
                $appliedFilters[] = [
                    'filter_id' => $filter->id,
                    'filter_name' => $filter->name,
                    'values' => $filter->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                        ];
                    })->toArray(),
                ];
            }
        }

        return $appliedFilters;
    }

    /**
     * Build filter query string from selected filters
     */
    public function buildFilterQueryString(array $selectedFilters): string
    {
        $params = [];

        foreach ($selectedFilters as $filterId => $valueIds) {
            if (empty($valueIds)) {
                continue;
            }

            $params["filter[{$filterId}]"] = implode(',', $valueIds);
        }

        return http_build_query($params);
    }

    /**
     * Parse filter query string to array
     */
    public function parseFilterQueryString(string $queryString): array
    {
        parse_str($queryString, $params);

        $filters = [];
        if (isset($params['filter']) && is_array($params['filter'])) {
            foreach ($params['filter'] as $filterId => $values) {
                $filters[$filterId] = is_array($values)
                    ? $values
                    : explode(',', $values);
            }
        }

        return $filters;
    }

    /**
     * Get price filter with min and max values
     */
    private function getPriceFilter($catalogId = null, array $selectedFilters = []): ?array
    {
        if (!class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            return null;
        }

        $query = \HolartWeb\AxoraCMS\Models\Shop\TProduct::query();

        // Apply catalog filter (including children) only if catalogId is provided
        if ($catalogId !== null) {
            $catalogIds = $this->getCatalogIdsWithChildren($catalogId);
            $query->whereIn('catalog_id', $catalogIds);
        }

        // Apply other selected filters (excluding price)
        foreach ($selectedFilters as $filterId => $valueIds) {
            if ($filterId === 'price') {
                continue;
            }

            if (!empty($valueIds)) {
                $query->whereHas('filterValues', function ($q) use ($valueIds) {
                    $q->whereIn('t_filter_values.id', $valueIds);
                });
            }
        }

        // Get min and max prices
        $minPrice = $query->min('price');
        $maxPrice = $query->max('price');

        if ($minPrice === null || $maxPrice === null) {
            return null;
        }

        // Get selected price range
        $selectedMin = $selectedFilters['price']['min'] ?? $minPrice;
        $selectedMax = $selectedFilters['price']['max'] ?? $maxPrice;

        return [
            'id' => 'price',
            'name' => 'Цена',
            'code' => 'price',
            'type' => 'range',
            'values' => [
                'min' => $minPrice,
                'max' => $maxPrice,
                'selected_min' => $selectedMin,
                'selected_max' => $selectedMax,
            ],
        ];
    }
}
