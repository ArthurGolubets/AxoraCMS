<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockSection;
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
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            $result->getCollection()->transform(fn($el) => $this->enrichEnumProperties($el, $infoBlock->fields));
            return $result;
        }

        $elements = $query->get();
        return $elements->map(fn($el) => $this->enrichEnumProperties($el, $infoBlock->fields));
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

    /**
     * Get breadcrumbs for info block element
     * Returns: Main - InfoBlock Name - Element Name
     *
     * @param string $infoBlockCode Info block code
     * @param int|null $elementId Element ID (optional)
     * @return array
     * @throws \Exception
     */
    public function getBreadcrumbs(string $infoBlockCode, ?int $elementId = null): array
    {
        $this->checkInfoBlocksModule();

        $breadcrumbs = [
            [
                'name' => 'Главная',
                'url' => '/',
            ]
        ];

        $infoBlock = $this->getInfoBlockByCode($infoBlockCode);
        if (!$infoBlock) {
            return $breadcrumbs;
        }

        // Add info block
        $breadcrumbs[] = [
            'id' => $infoBlock->id,
            'name' => $infoBlock->name,
            'code' => $infoBlock->code,
            'url' => '/info/' . $infoBlock->code,
        ];

        // Add element if provided
        if ($elementId !== null) {
            $element = $this->getElementById($infoBlockCode, $elementId);
            if ($element) {
                $breadcrumbs[] = [
                    'id' => $element->id,
                    'name' => $element->name,
                    'code' => $element->code,
                    'url' => '/info/' . $infoBlock->code . '/' . $element->code,
                ];
            }
        }

        return $breadcrumbs;
    }

    /**
     * Get enum options for info block fields
     *
     * @param string $code Info block code
     * @return array ['field_code' => [['code' => '...', 'title' => '...'], ...], ...]
     * @throws \Exception
     */
    public function getEnumOptions(string $code): array
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        $enums = [];

        foreach ($infoBlock->fields as $field) {
            if ($field->type === 'enum') {
                $options = $field->settings['options'] ?? [];
                $enums[$field->code] = array_map(fn($option) => [
                    'code'  => $option['code'],
                    'title' => $option['title'],
                ], $options);
            }
        }

        return $enums;
    }

    /**
     * Get sections list (flat) for catalog type info block
     *
     * @param string $code Info block code
     * @param bool $activeOnly Get only active sections
     * @return Collection
     * @throws \Exception
     */
    public function getSections(string $code, bool $activeOnly = true): Collection
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        $query = $infoBlock->sections()->orderBy('sort');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get sections tree with children for catalog type info block
     *
     * @param string $code Info block code
     * @param bool $activeOnly Get only active sections
     * @return array
     * @throws \Exception
     */
    public function getSectionsTree(string $code, bool $activeOnly = true): array
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        $query = $infoBlock->sections()->whereNull('parent_id')->orderBy('sort');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $rootSections = $query->get();

        return $rootSections->map(function ($section) use ($activeOnly) {
            return $this->buildSectionTree($section, $activeOnly);
        })->toArray();
    }

    /**
     * Build section tree recursively
     *
     * @param TInfoBlockSection $section
     * @param bool $activeOnly
     * @return array
     */
    protected function buildSectionTree(TInfoBlockSection $section, bool $activeOnly = true): array
    {
        $childrenQuery = $section->children()->orderBy('sort');

        if ($activeOnly) {
            $childrenQuery->where('is_active', true);
        }

        $children = $childrenQuery->get();

        return [
            'id' => $section->id,
            'name' => $section->name,
            'code' => $section->code,
            'image' => $section->image,
            'description' => $section->description,
            'is_active' => $section->is_active,
            'children' => $children->map(function ($child) use ($activeOnly) {
                return $this->buildSectionTree($child, $activeOnly);
            })->toArray(),
        ];
    }

    /**
     * Get elements of specific section
     *
     * @param string $code Info block code
     * @param int $sectionId Section ID
     * @param array $filter Additional filters
     * @param array $order Ordering
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @param bool $activeOnly Get only active elements
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getElementsBySection(
        string $code,
        int $sectionId,
        array $filter = [],
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1,
        bool $activeOnly = true
    ) {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        $filter['section_id'] = $sectionId;

        if ($activeOnly) {
            $filter['is_active'] = true;
        }

        return $this->getElements($code, $filter, $order, $perPage, $page);
    }

    /**
     * Get elements of section and all its subsections recursively
     *
     * @param string $code Info block code
     * @param int $sectionId Section ID
     * @param array $filter Additional filters
     * @param array $order Ordering
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @param bool $activeOnly Get only active elements
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getElementsBySectionRecursive(
        string $code,
        int $sectionId,
        array $filter = [],
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1,
        bool $activeOnly = true
    ) {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        $section = TInfoBlockSection::find($sectionId);

        if (!$section || $section->info_block_id !== $infoBlock->id) {
            throw new \Exception("Section with ID '{$sectionId}' not found in this info block");
        }

        // Get all descendant section IDs
        $sectionIds = $this->getDescendantSectionIds($section);
        $sectionIds[] = $sectionId; // Include current section

        $query = $infoBlock->elements()->whereIn('section_id', $sectionIds);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        // Apply additional filters
        $this->applyFilters($query, $filter);

        // Apply ordering
        $this->applyOrdering($query, $order);

        // Return paginated or all results
        if ($perPage !== null) {
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            $result->getCollection()->transform(fn($el) => $this->enrichEnumProperties($el, $infoBlock->fields));
            return $result;
        }

        $elements = $query->get();
        return $elements->map(fn($el) => $this->enrichEnumProperties($el, $infoBlock->fields));
    }

    /**
     * Get all descendant section IDs recursively
     *
     * @param TInfoBlockSection $section
     * @return array
     */
    protected function getDescendantSectionIds(TInfoBlockSection $section): array
    {
        $ids = [];

        $children = $section->children;

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantSectionIds($child));
        }

        return $ids;
    }

    /**
     * Get all elements from catalog type info block (from all sections)
     *
     * @param string $code Info block code
     * @param array $filter Additional filters
     * @param array $order Ordering
     * @param int|null $perPage Items per page
     * @param int $page Current page
     * @param bool $activeOnly Get only active elements
     * @return Collection|LengthAwarePaginator
     * @throws \Exception
     */
    public function getAllCatalogElements(
        string $code,
        array $filter = [],
        array $order = ['sort' => 'asc'],
        ?int $perPage = null,
        int $page = 1,
        bool $activeOnly = true
    ) {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        if ($activeOnly) {
            $filter['is_active'] = true;
        }

        return $this->getElements($code, $filter, $order, $perPage, $page);
    }

    /**
     * Get section by ID
     *
     * @param string $code Info block code
     * @param int $sectionId Section ID
     * @return TInfoBlockSection|null
     * @throws \Exception
     */
    public function getSectionById(string $code, int $sectionId): ?TInfoBlockSection
    {
        $infoBlock = $this->getInfoBlockByCode($code);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$code}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$code}' is not a catalog type");
        }

        return $infoBlock->sections()->where('id', $sectionId)->first();
    }

    /**
     * Get section by code
     *
     * @param string $infoBlockCode Info block code
     * @param string $sectionCode Section code
     * @return TInfoBlockSection|null
     * @throws \Exception
     */
    public function getSectionByCode(string $infoBlockCode, string $sectionCode): ?TInfoBlockSection
    {
        $infoBlock = $this->getInfoBlockByCode($infoBlockCode);

        if (!$infoBlock) {
            throw new \Exception("Info block with code '{$infoBlockCode}' not found");
        }

        if (!$infoBlock->isCatalog()) {
            throw new \Exception("Info block '{$infoBlockCode}' is not a catalog type");
        }

        return $infoBlock->sections()
            ->where('code', $sectionCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Apply filters to query with advanced operators
     *
     * Supported filter formats:
     * - Simple: ['field' => 'value'] - exact match
     * - Array: ['field' => ['value1', 'value2']] - IN clause
     * - Operators: ['field' => ['>', 100]] - comparison
     * - Like: ['field' => ['LIKE', '%text%']] - pattern matching
     *
     * @param $query
     * @param array $filter
     * @return void
     */
    protected function applyFilters($query, array $filter): void
    {
        foreach ($filter as $key => $value) {
            // Direct column filter
            if (in_array($key, ['id', 'code', 'name', 'is_active', 'info_block_id', 'section_id'])) {
                if (is_array($value)) {
                    // Check if it's an operator format: ['>', 100] or ['LIKE', '%text%']
                    if (count($value) === 2 && is_string($value[0]) && in_array(strtoupper($value[0]), ['>', '<', '>=', '<=', '=', '!=', 'LIKE', 'NOT LIKE'])) {
                        $operator = strtoupper($value[0]);
                        $operand = $value[1];
                        $query->where($key, $operator, $operand);
                    } else {
                        // Array filter (IN)
                        $query->whereIn($key, $value);
                    }
                } elseif ($key === 'name' && is_string($value)) {
                    $query->where('name', 'like', "%{$value}%");
                } else {
                    $query->where($key, $value);
                }
            } else {
                // Property filter (JSON)
                if (is_array($value)) {
                    // Check if it's an operator format
                    if (count($value) === 2 && is_string($value[0]) && in_array(strtoupper($value[0]), ['>', '<', '>=', '<=', '=', '!=', 'LIKE', 'NOT LIKE'])) {
                        $operator = strtoupper($value[0]);
                        $operand = $value[1];

                        if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                            // String pattern matching
                            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) {$operator} ?", [$operand]);
                        } else {
                            // Numeric comparison
                            $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) AS DECIMAL) {$operator} ?", [$operand]);
                        }
                    } else {
                        // Array filter (IN) for properties
                        $query->where(function($q) use ($key, $value) {
                            foreach ($value as $v) {
                                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) = ?", [$v]);
                            }
                        });
                    }
                } else {
                    // Simple filter
                    if (is_numeric($value)) {
                        // For numeric values, cast JSON value to number
                        $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) AS DECIMAL) = ?", [$value]);
                    } elseif (is_bool($value)) {
                        // For boolean values
                        $query->whereRaw("JSON_EXTRACT(properties, '$.{$key}') = ?", [$value ? 'true' : 'false']);
                    } else {
                        // For string values
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.{$key}')) = ?", [$value]);
                    }
                }
            }
        }
    }

    protected function enrichEnumProperties($element, $fields): mixed
    {
        $properties = $element->properties ?? [];

        foreach ($fields as $field) {
            if ($field->type !== 'enum') continue;

            $code = $properties[$field->code] ?? null;
            if ($code === null) continue;

            $options = $field->settings['options'] ?? [];
            $title = null;
            foreach ($options as $option) {
                if ($option['code'] === $code) {
                    $title = $option['title'];
                    break;
                }
            }

            $properties[$field->code] = [
                'code'  => $code,
                'title' => $title,
            ];
        }

        $element->properties = $properties;
        return $element;
    }
}
