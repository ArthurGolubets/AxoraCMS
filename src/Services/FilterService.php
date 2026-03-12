<?php

namespace HolartWeb\HolartCMS\Services;

use HolartWeb\HolartCMS\Models\Shop\TFilter;
use Illuminate\Support\Collection;

class FilterService
{
    /**
     * Get filters for a catalog with their values and product counts
     */
    public function getFiltersForCatalog($catalogId, array $selectedFilters = []): array
    {
        $filters = TFilter::with('activeValues')
            ->forCatalog($catalogId)
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

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

        return $filtersWithCounts->toArray();
    }

    /**
     * Count products in catalog with specific filter value
     */
    private function countProductsWithFilter($catalogId, $filterValueId, array $selectedFilters, $currentFilterId): int
    {
        if (!class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            return 0;
        }

        $query = \HolartWeb\HolartCMS\Models\Shop\TProduct::query()
            ->where('catalog_id', $catalogId);

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
     */
    public function filterProducts($catalogId, array $selectedFilters, $query = null)
    {
        if (!class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            return collect([]);
        }

        if ($query === null) {
            $query = \HolartWeb\HolartCMS\Models\Shop\TProduct::query()
                ->where('catalog_id', $catalogId);
        }

        // Apply each filter
        foreach ($selectedFilters as $filterId => $valueIds) {
            if (empty($valueIds)) {
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
}
