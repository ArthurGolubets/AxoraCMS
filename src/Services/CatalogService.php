<?php

namespace HolartWeb\HolartCMS\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    /**
     * Get catalog model class
     */
    protected function getCatalogModel(): ?string
    {
        if (!Schema::hasTable('t_catalogs')) {
            return null;
        }
        return config('holart-cms.models.catalog', \HolartWeb\HolartCMS\Models\Shop\TCatalog::class);
    }

    /**
     * Get product model class
     */
    protected function getProductModel(): ?string
    {
        if (!Schema::hasTable('t_products')) {
            return null;
        }
        return config('holart-cms.models.product', \HolartWeb\HolartCMS\Models\Shop\TProduct::class);
    }

    /**
     * Get entire catalog tree
     *
     * @param bool $activeOnly Only active catalogs
     * @return array
     */
    public function getCatalogTree(bool $activeOnly = false): array
    {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return [];
        }

        $query = $catalogModel::query()->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $catalogs = $query->get();

        return $this->buildTree($catalogs);
    }

    /**
     * Build hierarchical tree from flat collection
     */
    protected function buildTree($catalogs, $parentId = null): array
    {
        $branch = [];

        foreach ($catalogs as $catalog) {
            if ($catalog->parent_id == $parentId) {
                $children = $this->buildTree($catalogs, $catalog->id);

                $catalogArray = $catalog->toArray();
                if ($children) {
                    $catalogArray['children'] = $children;
                }

                $branch[] = $catalogArray;
            }
        }

        return $branch;
    }

    /**
     * Get catalog with its children
     *
     * @param int $catalogId
     * @param bool $activeOnly Only active children
     * @return array|null
     */
    public function getCatalogWithChildren(int $catalogId, bool $activeOnly = false): ?array
    {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return null;
        }

        $catalog = $catalogModel::find($catalogId);
        if (!$catalog) {
            return null;
        }

        $query = $catalogModel::where('parent_id', $catalogId)->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $children = $query->get();

        $result = $catalog->toArray();
        $result['children'] = $children->toArray();

        return $result;
    }

    /**
     * Get all products from catalog
     *
     * @param int $catalogId
     * @param int|null $limit Limit number of products (null for all)
     * @param int $page Page number for pagination
     * @param bool $activeOnly Only active products
     * @return LengthAwarePaginator|Collection
     */
    public function getCatalogProducts(
        int $catalogId,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = false
    ) {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return collect([]);
        }

        $query = $productModel::where('catalog_id', $catalogId)->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($limit !== null) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get catalogs by characteristics
     *
     * @param array $characteristics ['code' => value] pairs
     * @param int|null $limit
     * @param int $page
     * @param bool $activeOnly
     * @return LengthAwarePaginator|Collection
     */
    public function getCatalogsByCharacteristics(
        array $characteristics,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = false
    ) {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return collect([]);
        }

        $query = $catalogModel::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        // Filter by characteristics
        foreach ($characteristics as $code => $value) {
            $query->whereRaw(
                "JSON_SEARCH(addition_info, 'one', ?, null, '$[*].code') IS NOT NULL",
                [$code]
            );

            if (is_bool($value)) {
                $query->whereRaw(
                    "EXISTS (
                        SELECT 1 FROM JSON_TABLE(
                            addition_info,
                            '$[*]' COLUMNS(
                                code VARCHAR(255) PATH '$.code',
                                value BOOLEAN PATH '$.value'
                            )
                        ) AS jt
                        WHERE jt.code = ? AND jt.value = ?
                    )",
                    [$code, $value ? 1 : 0]
                );
            } else {
                $query->whereRaw(
                    "EXISTS (
                        SELECT 1 FROM JSON_TABLE(
                            addition_info,
                            '$[*]' COLUMNS(
                                code VARCHAR(255) PATH '$.code',
                                value TEXT PATH '$.value',
                                values JSON PATH '$.values'
                            )
                        ) AS jt
                        WHERE jt.code = ? AND (
                            jt.value = ? OR
                            JSON_CONTAINS(jt.values, ?)
                        )
                    )",
                    [$code, $value, json_encode($value)]
                );
            }
        }

        $query->orderBy('name');

        if ($limit !== null) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get products by characteristics
     *
     * @param array $characteristics ['code' => value] pairs
     * @param int|null $catalogId Optional catalog filter
     * @param int|null $limit
     * @param int $page
     * @param bool $activeOnly
     * @return LengthAwarePaginator|Collection
     */
    public function getProductsByCharacteristics(
        array $characteristics,
        ?int $catalogId = null,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = false
    ) {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return collect([]);
        }

        $query = $productModel::query();

        if ($catalogId) {
            $query->where('catalog_id', $catalogId);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        // Filter by characteristics
        foreach ($characteristics as $code => $value) {
            $query->whereRaw(
                "JSON_SEARCH(addition_info, 'one', ?, null, '$[*].code') IS NOT NULL",
                [$code]
            );

            if (is_bool($value)) {
                $query->whereRaw(
                    "EXISTS (
                        SELECT 1 FROM JSON_TABLE(
                            addition_info,
                            '$[*]' COLUMNS(
                                code VARCHAR(255) PATH '$.code',
                                value BOOLEAN PATH '$.value'
                            )
                        ) AS jt
                        WHERE jt.code = ? AND jt.value = ?
                    )",
                    [$code, $value ? 1 : 0]
                );
            } else {
                $query->whereRaw(
                    "EXISTS (
                        SELECT 1 FROM JSON_TABLE(
                            addition_info,
                            '$[*]' COLUMNS(
                                code VARCHAR(255) PATH '$.code',
                                value TEXT PATH '$.value',
                                values JSON PATH '$.values'
                            )
                        ) AS jt
                        WHERE jt.code = ? AND (
                            jt.value = ? OR
                            JSON_CONTAINS(jt.values, ?)
                        )
                    )",
                    [$code, $value, json_encode($value)]
                );
            }
        }

        $query->orderBy('name');

        if ($limit !== null) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get product variants
     *
     * @param int $productId
     * @return array
     */
    public function getProductVariants(int $productId): array
    {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return [];
        }

        $product = $productModel::find($productId);
        if (!$product) {
            return [];
        }

        return $product->variants ?? [];
    }

    /**
     * Get catalogs with filters
     *
     * @param array $filters ['is_active' => true, 'parent_id' => null, etc.]
     * @param int|null $limit
     * @param int $page
     * @param string $orderBy
     * @param string $orderDirection
     * @return LengthAwarePaginator|Collection
     */
    public function getCatalogsWithFilters(
        array $filters = [],
        ?int $limit = null,
        int $page = 1,
        string $orderBy = 'name',
        string $orderDirection = 'asc'
    ) {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return collect([]);
        }

        $query = $catalogModel::query();

        foreach ($filters as $field => $value) {
            if ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        $query->orderBy($orderBy, $orderDirection);

        if ($limit !== null) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get products with filters
     *
     * @param array $filters ['is_hot' => true, 'is_new' => true, etc.]
     * @param int|null $catalogId Optional catalog filter
     * @param int|null $limit
     * @param int $page
     * @param string $orderBy
     * @param string $orderDirection
     * @return LengthAwarePaginator|Collection
     */
    public function getProductsWithFilters(
        array $filters = [],
        ?int $catalogId = null,
        ?int $limit = null,
        int $page = 1,
        string $orderBy = 'name',
        string $orderDirection = 'asc'
    ) {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return collect([]);
        }

        $query = $productModel::query();

        if ($catalogId) {
            $query->where('catalog_id', $catalogId);
        }

        foreach ($filters as $field => $value) {
            if ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        $query->orderBy($orderBy, $orderDirection);

        if ($limit !== null) {
            return $query->paginate($limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * Get catalog by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function getCatalogBySlug(string $slug): ?array
    {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return null;
        }

        $catalog = $catalogModel::where('slug', $slug)->first();

        return $catalog ? $catalog->toArray() : null;
    }

    /**
     * Get product by slug
     *
     * @param string $slug
     * @param bool $withVariants Include variants
     * @return array|null
     */
    public function getProductBySlug(string $slug, bool $withVariants = true): ?array
    {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return null;
        }

        $product = $productModel::where('slug', $slug)->first();

        if (!$product) {
            return null;
        }

        $result = $product->toArray();

        if ($withVariants && isset($product->variants)) {
            $result['variants'] = $product->variants;
        }

        return $result;
    }

    /**
     * Get breadcrumbs for catalog
     *
     * @param int $catalogId
     * @return array
     */
    public function getCatalogBreadcrumbs(int $catalogId): array
    {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return [];
        }

        $breadcrumbs = [];
        $catalog = $catalogModel::find($catalogId);

        while ($catalog) {
            array_unshift($breadcrumbs, [
                'id' => $catalog->id,
                'name' => $catalog->name,
                'slug' => $catalog->slug,
            ]);

            $catalog = $catalog->parent_id ? $catalogModel::find($catalog->parent_id) : null;
        }

        return $breadcrumbs;
    }

    /**
     * Search products
     *
     * @param string $query Search query
     * @param int|null $catalogId Optional catalog filter
     * @param int|null $limit
     * @param int $page
     * @param bool $activeOnly
     * @return LengthAwarePaginator|Collection
     */
    public function searchProducts(
        string $query,
        ?int $catalogId = null,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = false
    ) {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return collect([]);
        }

        $queryBuilder = $productModel::query();

        if ($catalogId) {
            $queryBuilder->where('catalog_id', $catalogId);
        }

        if ($activeOnly) {
            $queryBuilder->where('is_active', true);
        }

        $queryBuilder->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%")
              ->orWhere('sku', 'LIKE', "%{$query}%");
        });

        $queryBuilder->orderBy('name');

        if ($limit !== null) {
            return $queryBuilder->paginate($limit, ['*'], 'page', $page);
        }

        return $queryBuilder->get();
    }

    /**
     * Search catalogs
     *
     * @param string $query Search query
     * @param int|null $limit
     * @param int $page
     * @param bool $activeOnly
     * @return LengthAwarePaginator|Collection
     */
    public function searchCatalogs(
        string $query,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = false
    ) {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return collect([]);
        }

        $queryBuilder = $catalogModel::query();

        if ($activeOnly) {
            $queryBuilder->where('is_active', true);
        }

        $queryBuilder->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        });

        $queryBuilder->orderBy('name');

        if ($limit !== null) {
            return $queryBuilder->paginate($limit, ['*'], 'page', $page);
        }

        return $queryBuilder->get();
    }

    /**
     * Get related products (from same catalog)
     *
     * @param int $productId
     * @param int $limit
     * @param bool $activeOnly
     * @return Collection
     */
    public function getRelatedProducts(
        int $productId,
        int $limit = 6,
        bool $activeOnly = true
    ): Collection {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return collect([]);
        }

        $product = $productModel::find($productId);
        if (!$product) {
            return collect([]);
        }

        $query = $productModel::where('catalog_id', $product->catalog_id)
            ->where('id', '!=', $productId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get featured products (is_hot, is_new, is_recommended)
     *
     * @param string $type 'hot', 'new', or 'recommended'
     * @param int|null $catalogId Optional catalog filter
     * @param int|null $limit
     * @param int $page
     * @param bool $activeOnly
     * @return LengthAwarePaginator|Collection
     */
    public function getFeaturedProducts(
        string $type,
        ?int $catalogId = null,
        ?int $limit = null,
        int $page = 1,
        bool $activeOnly = true
    ) {
        $fieldMap = [
            'hot' => 'is_hot',
            'new' => 'is_new',
            'recommended' => 'is_recommended',
        ];

        if (!isset($fieldMap[$type])) {
            return collect([]);
        }

        return $this->getProductsWithFilters(
            [$fieldMap[$type] => true, 'is_active' => $activeOnly],
            $catalogId,
            $limit,
            $page
        );
    }

    /**
     * Get all available characteristic codes from products
     *
     * @param int|null $catalogId Optional catalog filter
     * @return array Array of unique characteristic codes
     */
    public function getAvailableProductCharacteristics(?int $catalogId = null): array
    {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return [];
        }

        $query = $productModel::whereNotNull('addition_info');

        if ($catalogId) {
            $query->where('catalog_id', $catalogId);
        }

        $products = $query->get();
        $characteristics = [];

        foreach ($products as $product) {
            if (is_array($product->addition_info)) {
                foreach ($product->addition_info as $char) {
                    if (isset($char['code']) && !in_array($char['code'], $characteristics)) {
                        $characteristics[] = $char['code'];
                    }
                }
            }
        }

        return $characteristics;
    }

    /**
     * Get all available characteristic codes from catalogs
     *
     * @return array Array of unique characteristic codes
     */
    public function getAvailableCatalogCharacteristics(): array
    {
        $catalogModel = $this->getCatalogModel();
        if (!$catalogModel) {
            return [];
        }

        $catalogs = $catalogModel::whereNotNull('addition_info')->get();
        $characteristics = [];

        foreach ($catalogs as $catalog) {
            if (is_array($catalog->addition_info)) {
                foreach ($catalog->addition_info as $char) {
                    if (isset($char['code']) && !in_array($char['code'], $characteristics)) {
                        $characteristics[] = $char['code'];
                    }
                }
            }
        }

        return $characteristics;
    }

    /**
     * Get product count by catalog
     *
     * @param int $catalogId
     * @param bool $activeOnly
     * @return int
     */
    public function getCatalogProductCount(int $catalogId, bool $activeOnly = false): int
    {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return 0;
        }

        $query = $productModel::where('catalog_id', $catalogId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    /**
     * Get price range for catalog products
     *
     * @param int $catalogId
     * @param bool $activeOnly
     * @return array ['min' => float, 'max' => float]
     */
    public function getCatalogPriceRange(int $catalogId, bool $activeOnly = false): array
    {
        $productModel = $this->getProductModel();
        if (!$productModel) {
            return ['min' => 0, 'max' => 0];
        }

        $query = $productModel::where('catalog_id', $catalogId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return [
            'min' => $query->min('price') ?? 0,
            'max' => $query->max('price') ?? 0,
        ];
    }
}
