<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Shop;

use App\Models\TCatalog;
use HolartWeb\HolartCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    /**
     * Get all catalogs with hierarchy
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');

        if ($search) {
            // Search in both catalogs and products
            $catalogs = TCatalog::where('name', 'like', "%{$search}%")
                ->orWhereHas('products', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                })
                ->with(['children', 'products' => function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                }])
                ->get();
        } else {
            // Get root categories (no parent)
            $catalogs = TCatalog::with(['children', 'products'])
                ->whereNull('parent_id')
                ->get();
        }

        return response()->json($catalogs);
    }

    /**
     * Get catalog tree structure (only root categories, children loaded on demand)
     */
    public function tree(): JsonResponse
    {
        $catalogs = TCatalog::whereNull('parent_id')
            ->withCount(['children', 'products'])
            ->get();

        return response()->json($catalogs);
    }

    /**
     * Get all catalogs in flat list with parent info
     */
    public function list(): JsonResponse
    {
        $catalogs = TCatalog::with('parent')
            ->orderBy('name')
            ->get();

        return response()->json($catalogs);
    }

    /**
     * Get single catalog with products and filters
     */
    public function show($id): JsonResponse
    {
        $catalog = TCatalog::with(['parent', 'children', 'products.variants'])
            ->findOrFail($id);

        // Get filters for this catalog
        $filters = [];
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TFilter')) {
            $filterClass = 'HolartWeb\HolartCMS\Models\Shop\TFilter';
            $filters = $filterClass::with('values')
                ->where('catalog_id', $id)
                ->orderBy('sort')
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'catalog' => $catalog,
            'breadcrumbs' => $catalog->getBreadcrumbs(),
            'filters' => $filters,
        ]);
    }

    /**
     * Create new catalog
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_catalogs,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:t_catalogs,slug',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'image' => 'nullable|string',
            'content' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $catalog = TCatalog::create($validated);

        // Log activity
        TAdminAction::log('created', 'catalog', $catalog->id,
            'Создана категория "' . $catalog->name . '"');

        return response()->json($catalog, 201);
    }

    /**
     * Update catalog
     */
    public function update(Request $request, $id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_catalogs,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:t_catalogs,slug,' . $id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'image' => 'nullable|string',
            'content' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Prevent circular reference
        if ($validated['parent_id'] == $id) {
            return response()->json(['message' => 'Категория не может быть родителем самой себя'], 422);
        }

        $oldData = $catalog->getOriginal();
        $catalog->update($validated);

        // Log activity
        TAdminAction::log('updated', 'catalog', $catalog->id,
            'Обновлена категория "' . $catalog->name . '"', [
            'old' => $oldData,
            'new' => $catalog->getAttributes()
        ]);

        return response()->json($catalog);
    }

    /**
     * Delete catalog
     */
    public function destroy($id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);
        $catalogName = $catalog->name;

        // Check if has products
        if ($catalog->products()->exists()) {
            return response()->json([
                'message' => 'Невозможно удалить категорию с товарами'
            ], 422);
        }

        // Check if has children
        if ($catalog->hasChildren()) {
            return response()->json([
                'message' => 'Невозможно удалить категорию с подкатегориями'
            ], 422);
        }

        $catalog->delete();

        // Log activity
        TAdminAction::log('deleted', 'catalog', $id,
            'Удалена категория "' . $catalogName . '"');

        return response()->json(['message' => 'Категория удалена']);
    }

    /**
     * Get children of a catalog
     */
    public function children($id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);
        $children = $catalog->children()->with('products')->get();

        return response()->json($children);
    }
}
