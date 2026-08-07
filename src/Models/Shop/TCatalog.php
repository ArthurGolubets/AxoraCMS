<?php

namespace HolartWeb\AxoraCMS\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TCatalog extends Model
{
    protected $table = 't_catalogs';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        '1c_id',
        'title',
        'description',
        'keywords',
        'image',
        'content',
        'is_active',
        'addition_info',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'addition_info' => 'array',
    ];

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TCatalog::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(TCatalog::class, 'parent_id');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(TProduct::class, 'catalog_id');
    }

    /**
     * Get properties for this catalog
     */
    public function properties(): HasMany
    {
        return $this->hasMany(TCatalogProperty::class, 'catalog_id')->orderBy('sort_order');
    }

    /**
     * Get all properties including inherited from parent catalogs
     */
    public function getAllProperties()
    {
        $properties = collect();
        $catalog = $this;

        // Collect properties from current catalog and all parents
        while ($catalog) {
            $catalogProperties = $catalog->properties;
            foreach ($catalogProperties as $property) {
                // Add only if not already exists (child properties override parent)
                if (!$properties->contains('code', $property->code)) {
                    $properties->push($property);
                }
            }
            $catalog = $catalog->parent;
        }

        return $properties->sortBy('sort_order')->values();
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get breadcrumb path
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumbs, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
            $category = $category->parent;
        }

        return $breadcrumbs;
    }

    public function getDescendantIds(): array
    {
        $ids = [$this->id];

        $children = $this->children()->pluck('id');

        foreach ($children as $childId) {
            $child = self::find($childId);
            $ids = array_merge($ids, $child->getDescendantIds());
        }

        return $ids;
    }

    /**
     * Подсчитать количество товаров в категории и всех дочерних категориях
     * (простой вариант, подходит для небольших/средних деревьев)
     */
    public function getProductsCountWithChildren(): int
    {
        $catalogIds = $this->getDescendantIds();

        return TProduct::whereIn('catalog_id', $catalogIds)->count();
    }

    /**
     * Подсчитать количество товаров в категории и всех дочерних категориях
     * (оптимизированный вариант — один SQL-запрос через recursive CTE, MySQL 8+/PostgreSQL)
     */
    public function getProductsCountWithChildrenOptimized(): int
    {
        $result = \DB::selectOne("
        WITH RECURSIVE catalog_tree AS (
            SELECT id FROM t_catalogs WHERE id = ?
            UNION ALL
            SELECT tc.id
            FROM t_catalogs tc
            INNER JOIN catalog_tree ct ON tc.parent_id = ct.id
        )
        SELECT COUNT(*) as products_count
        FROM t_products
        WHERE catalog_id IN (SELECT id FROM catalog_tree)
    ", [$this->id]);

        return (int) $result->products_count;
    }
}
