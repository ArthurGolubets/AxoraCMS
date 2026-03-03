<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Shop;

use App\Models\TProduct;
use App\Models\TProductVariant;
use HolartWeb\HolartCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    /**
     * Get all products with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = TProduct::with(['catalog', 'variants']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by catalog
        if ($catalogId = $request->get('catalog_id')) {
            $query->where('catalog_id', $catalogId);
        }

        // Filter by flags
        if ($request->has('is_new')) {
            $query->where('is_new', $request->boolean('is_new'));
        }
        if ($request->has('is_hot')) {
            $query->where('is_hot', $request->boolean('is_hot'));
        }
        if ($request->has('is_recommended')) {
            $query->where('is_recommended', $request->boolean('is_recommended'));
        }

        // Price range
        if ($minPrice = $request->get('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->get('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($products);
    }

    /**
     * Get single product
     */
    public function show($id): JsonResponse
    {
        $product = TProduct::with(['catalog', 'variants'])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Create new product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'catalog_id' => 'required|exists:t_catalogs,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:t_products,slug',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:t_products,sku',
            'tags' => 'nullable|array',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_recommended' => 'boolean',
            'content' => 'nullable|string',
            'gallery' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:t_product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.old_price' => 'nullable|numeric|min:0',
            'variants.*.attributes' => 'nullable|array',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = TProduct::generateSlug($validated['name']);
        }

        // Extract variants data
        $variants = $validated['variants'] ?? [];
        unset($validated['variants']);

        $product = TProduct::create($validated);

        // Create variants if provided
        foreach ($variants as $variantData) {
            $product->variants()->create($variantData);
        }

        // Log activity
        TAdminAction::log('created', 'product', $product->id,
            'Создан товар "' . $product->name . '" (SKU: ' . $product->sku . ')');

        return response()->json($product->load('variants'), 201);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = TProduct::findOrFail($id);

        $validated = $request->validate([
            'catalog_id' => 'required|exists:t_catalogs,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:t_products,slug,' . $id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:t_products,sku,' . $id,
            'tags' => 'nullable|array',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_recommended' => 'boolean',
            'content' => 'nullable|string',
            'gallery' => 'nullable|array',
            'variants' => 'nullable|array',
        ]);

        // Handle variants update
        if (isset($validated['variants'])) {
            $variants = $validated['variants'];
            unset($validated['variants']);

            // Delete old variants
            $product->variants()->delete();

            // Create new variants
            foreach ($variants as $variantData) {
                $product->variants()->create($variantData);
            }
        }

        $oldData = $product->getOriginal();
        $product->update($validated);

        // Log activity
        TAdminAction::log('updated', 'product', $product->id,
            'Обновлен товар "' . $product->name . '" (SKU: ' . $product->sku . ')', [
            'old' => $oldData,
            'new' => $product->getAttributes()
        ]);

        return response()->json($product->load('variants'));
    }

    /**
     * Delete product
     */
    public function destroy($id): JsonResponse
    {
        $product = TProduct::findOrFail($id);
        $productName = $product->name;
        $productSku = $product->sku;

        $product->delete();

        // Log activity
        TAdminAction::log('deleted', 'product', $id,
            'Удален товар "' . $productName . '" (SKU: ' . $productSku . ')');

        return response()->json(['message' => 'Товар удален']);
    }

    /**
     * Bulk delete products
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:t_products,id',
        ]);

        $count = count($validated['ids']);
        TProduct::whereIn('id', $validated['ids'])->delete();

        // Log activity
        TAdminAction::log('deleted', 'product', null,
            'Массовое удаление товаров (количество: ' . $count . ')');

        return response()->json(['message' => 'Товары удалены']);
    }
}
