<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class InfoBlockService
{
    /**
     * Check if InfoBlocks module is installed
     *
     * @throws \Exception
     */
    protected function checkInfoBlocksModule(): void
    {
        if (!Schema::hasTable('t_info_blocks')) {
            throw new \Exception('InfoBlocks module is not installed');
        }
    }

    /**
     * Get info block by code
     *
     * @param string $code
     * @return TInfoBlock|null
     */
    public function getInfoBlockByCode(string $code): ?TInfoBlock
    {
        $this->checkInfoBlocksModule();

        return TInfoBlock::where('code', $code)
            ->where('is_active', true)
            ->with('fields')
            ->first();
    }

    /**
     * Get elements of info block with filter and pagination
     *
     * @param string $code Info block code
     * @param array $filter Filters to apply ['field' => 'value']
     * @param array $order Ordering ['field' => 'asc|desc']
     * @param int|null $perPage Items per page (null for no pagination)
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getElements(
        string $code,
        array $filter = [],
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        // Apply filters
        $this->applyFilters($query, $filter);

        // Apply ordering
        $this->applyOrdering($query, $order);

        // Return paginated or all results
        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get active elements of info block with pagination
     *
     * @param string $code Info block code
     * @param array $order Ordering ['field' => 'asc|desc']
     * @param int|null $perPage Items per page (null for no pagination)
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getActiveElements(
        string $code,
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        return $this->getElements($code, ['is_active' => true], $order, $perPage, $page);
    }

    /**
     * Get element by ID
     *
     * @param string $code Info block code
     * @param int $id Element ID
     * @return TInfoBlockElement|null
     * @throws \Exception
     */
    public function getElementById(string $code, int $id): ?TInfoBlockElement
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        return $infoBlock->elements()->where('id', $id)->first();
    }

    /**
     * Get element by code
     *
     * @param string $infoBlockCode Info block code
     * @param string $elementCode Element code
     * @return TInfoBlockElement|null
     * @throws \Exception
     */
    public function getElementByCode(string $infoBlockCode, string $elementCode): ?TInfoBlockElement
    {
        $infoBlock = $this->getInfoBlockByCode($infoBlockCode);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$infoBlockCode}' not found");
        }

        return $infoBlock->elements()
            ->where('code', $elementCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get first element
     *
     * @param string $code Info block code
     * @param array $filter Filters to apply
     * @param array $order Ordering
     * @return TInfoBlockElement|null
     * @throws \Exception
     */
    public function getFirstElement(
        string $code,
        array $filter = [],
        array $order = ['sort' => 'asc']
    ): ?TInfoBlockElement {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);
        $this->applyOrdering($query, $order);

        return $query->first();
    }

    /**
     * Get random elements
     *
     * @param string $code Info block code
     * @param int $count Number of elements
     * @param array $filter Filters to apply
     * @return Collection
     * @throws \Exception
     */
    public function getRandomElements(string $code, int $count = 1, array $filter = []): Collection
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);

        return $query->inRandomOrder()->limit($count)->get();
    }

    /**
     * Count elements
     *
     * @param string $code Info block code
     * @param array $filter Filters to apply
     * @return int
     * @throws \Exception
     */
    public function countElements(string $code, array $filter = []): int
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);

        return $query->count();
    }

    /**
     * Get elements with specific property value
     *
     * @param string $code Info block code
     * @param string $propertyCode Property code
     * @param mixed $value Property value
     * @param array $order Ordering
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getElementsByProperty(
        string $code,
        string $propertyCode,
        $value,
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1
    ) {
        return $this->getElements($code, [$propertyCode => $value], $order, $perPage, $page);
    }

    /**
     * Get elements grouped by property
     *
     * @param string $code Info block code
     * @param string $propertyCode Property code to group by
     * @param array $filter Additional filters
     * @return array
     * @throws \Exception
     */
    public function getElementsGroupedByProperty(
        string $code,
        string $propertyCode,
        array $filter = []
    ): array {
        $elements = $this->getElements($code, $filter);

        $grouped = [];

        foreach ($elements as $element) {
            $key = $element->getProperty($propertyCode, 'uncategorized');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }

            $grouped[$key][] = $element;
        }

        return $grouped;
    }

    /**
     * Check if element exists
     *
     * @param string $code Info block code
     * @param int $id Element ID
     * @return bool
     * @throws \Exception
     */
    public function elementExists(string $code, int $id): bool
    {
        return $this->getElementById($code, $id) !== null;
    }

    /**
     * Check if element exists by code
     *
     * @param string $infoBlockCode Info block code
     * @param string $elementCode Element code
     * @return bool
     * @throws \Exception
     */
    public function elementExistsByCode(string $infoBlockCode, string $elementCode): bool
    {
        return $this->getElementByCode($infoBlockCode, $elementCode) !== null;
    }

    /**
     * Get element with properties and fields info
     *
     * @param string $code Info block code
     * @param int $id Element ID
     * @return array|null
     * @throws \Exception
     */
    public function getElementWithFields(string $code, int $id): ?array
    {
        $element = $this->getElementById($code, $id);

        if (!$element) {
            return null;
        }

        return [
            'element' => $element,
            'properties' => $element->getPropertiesWithFields(),
        ];
    }

    /**
     * Get latest elements
     *
     * @param string $code Info block code
     * @param int $count Number of elements
     * @param array $filter Filters to apply
     * @return Collection
     * @throws \Exception
     */
    public function getLatestElements(string $code, int $count = 10, array $filter = []): Collection
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);

        return $query->orderBy('created_at', 'desc')->limit($count)->get();
    }

    /**
     * Get popular elements (by views or other metric from properties)
     *
     * @param string $code Info block code
     * @param string $metricProperty Property code for metric (e.g., 'views', 'rating')
     * @param int $count Number of elements
     * @param array $filter Filters to apply
     * @return Collection
     * @throws \Exception
     */
    public function getPopularElements(
        string $code,
        string $metricProperty = 'views',
        int $count = 10,
        array $filter = []
    ): Collection {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);

        return $query->orderByRaw("CAST(JSON_EXTRACT(properties, '$.{$metricProperty}') AS UNSIGNED) DESC")
            ->limit($count)
            ->get();
    }

    /**
     * Search elements by name
     *
     * @param string $code Info block code
     * @param string $search Search query
     * @param array $filter Additional filters
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function searchElements(
        string $code,
        string $search,
        array $filter = [],
        ?int $perPage = null,
        int $page = 1
    ) {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $query = $infoBlock->elements();

        $this->applyFilters($query, $filter);

        $query->where('name', 'like', "%{$search}%");

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Apply filters to query
     *
     * @param $query
     * @param array $filter
     * @return void
     */
    protected function applyFilters($query, array $filter): void
    {
        foreach ($filter as $key => $value) {
            if (in_array($key, ['id', 'code', 'name', 'is_active', 'info_block_id'])) {
                // Direct column filter
                if ($key === 'name' && is_string($value)) {
                    $query->where('name', 'like', "%{$value}%");
                } else {
                    $query->where($key, $value);
                }
            } else {
                // Property filter
                if (is_array($value)) {
                    // Array filter (IN) - for filtering like ['red', 'blue', 'green']
                    $query->where(function($q) use ($key, $value) {
                        foreach ($value as $v) {
                            $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) = ?", [$v]);
                        }
                    });
                } else {
                    // Simple filter - JSON_UNQUOTE removes quotes from JSON strings
                    if (is_numeric($value)) {
                        // For numeric values, cast JSON value to number
                        $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) AS DECIMAL) = ?", [$value]);
                    } elseif (is_bool($value)) {
                        // For boolean values
                        $query->whereRaw("JSON_EXTRACT(properties, '$.{$key}') = ?", [$value ? 'true' : 'false']);
                    } else {
                        // For string values like 'red', 'blue', etc.
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) = ?", [$value]);
                    }
                }
            }
        }
    }

    /**
     * Apply ordering to query
     *
     * @param $query
     * @param array $order
     * @return void
     */
    protected function applyOrdering($query, array $order): void
    {
        foreach ($order as $field => $direction) {
            $direction = strtolower($direction);

            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = 'asc';
            }

            if (in_array($field, ['id', 'name', 'code', 'sort', 'created_at', 'updated_at', 'is_active'])) {
                // Direct column ordering
                $query->orderBy($field, $direction);
            } else {
                // Property ordering
                $query->orderByRaw("JSON_EXTRACT(properties, '$.{$field}') {$direction}");
            }
        }
    }
}
