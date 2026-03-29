<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Shop;

use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\Shop\TProductVariant;
use HolartWeb\AxoraCMS\Models\TAdminAction;
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
     * Get single product with filters
     */
    public function show($id): JsonResponse
    {
        $product = TProduct::with(['catalog', 'variants', 'propertyValues.property'])->findOrFail($id);

        // Get available filters for this product's catalog
        $availableFilters = [];
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TFilter') && $product->catalog_id) {
            $filterClass = 'HolartWeb\AxoraCMS\Models\Shop\TFilter';
            $availableFilters = $filterClass::with('values')
                ->forCatalog($product->catalog_id)
                ->active()
                ->orderBy('sort')
                ->get();
        }

        // Get currently assigned filter values
        $assignedFilters = [];
        if (method_exists($product, 'getFiltersWithValues')) {
            $assignedFilters = $product->getFiltersWithValues();
        }

        // Get available properties for this product's catalog
        $availableProperties = [];
        if ($product->catalog && method_exists($product->catalog, 'getAllProperties')) {
            $availableProperties = $product->catalog->getAllProperties()->map(function($prop) use ($product) {
                $prop->is_inherited = $prop->catalog_id !== $product->catalog_id;
                return $prop;
            });
        }

        // Get property values - format for Vue: {property_id: value}
        $propertyValuesFormatted = [];
        foreach ($product->propertyValues as $pv) {
            $value = $pv->value;
            // Try to decode JSON for multiple values
            $decoded = json_decode($value, true);
            $propertyValuesFormatted[$pv->property_id] = $decoded !== null ? $decoded : $value;
        }

        return response()->json([
            'product' => $product,
            'available_filters' => $availableFilters,
            'assigned_filters' => $assignedFilters,
            'available_properties' => $availableProperties,
            'property_values' => $propertyValuesFormatted,
        ]);
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
            'main_image' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_recommended' => 'boolean',
            'is_active' => 'nullable|boolean',
            'content' => 'nullable|string',
            'gallery' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:t_product_variants,sku',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.old_price' => 'nullable|numeric|min:0',
            'variants.*.attributes' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'filter_values.*' => 'exists:t_filter_values,id',
            'addition_info' => 'nullable|array',
            'property_values' => 'nullable|array',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = TProduct::generateSlug($validated['name']);
        }

        // Extract variants data
        $variants = $validated['variants'] ?? [];
        unset($validated['variants']);

        // Extract filter values
        $filterValues = $validated['filter_values'] ?? [];
        unset($validated['filter_values']);

        // Extract property values
        $propertyValues = $validated['property_values'] ?? [];
        unset($validated['property_values']);

        $product = TProduct::create($validated);

        // Create variants if provided
        foreach ($variants as $variantData) {
            $product->variants()->create($variantData);
        }

        // Sync filter values if provided
        if (!empty($filterValues) && method_exists($product, 'syncFilterValues')) {
            $product->syncFilterValues($filterValues);
        }

        // Save property values if provided
        if (!empty($propertyValues) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductPropertyValue')) {
            foreach ($propertyValues as $propertyId => $value) {
                // Skip null, empty string, or empty arrays
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    continue;
                }

                // For arrays, filter out empty values and encode
                if (is_array($value)) {
                    $value = array_values(array_filter($value, function($v) {
                        return $v !== null && $v !== '';
                    }));

                    // Skip if array is empty after filtering
                    if (empty($value)) {
                        continue;
                    }

                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $product->propertyValues()->create([
                    'property_id' => $propertyId,
                    'value' => $value,
                ]);
            }
        }

        // Log activity
        TAdminAction::log('created', 'product', $product->id,
            'Создан товар "' . $product->name . '" (SKU: ' . $product->sku . ')');

        return response()->json($product->load(['variants', 'propertyValues.property']), 201);
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
            'main_image' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_new' => 'boolean',
            'is_hot' => 'boolean',
            'is_recommended' => 'boolean',
            'is_active' => 'nullable|boolean',
            'content' => 'nullable|string',
            'gallery' => 'nullable|array',
            'variants' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'filter_values.*' => 'exists:t_filter_values,id',
            'addition_info' => 'nullable|array',
            'property_values' => 'nullable|array',
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

        // Handle filter values update
        if (isset($validated['filter_values'])) {
            $filterValues = $validated['filter_values'];
            unset($validated['filter_values']);

            if (method_exists($product, 'syncFilterValues')) {
                $product->syncFilterValues($filterValues);
            }
        }

        // Handle property values update
        if (isset($validated['property_values']) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductPropertyValue')) {
            $propertyValues = $validated['property_values'];
            unset($validated['property_values']);

            // Delete old property values
            $product->propertyValues()->delete();

            // Create new property values
            foreach ($propertyValues as $propertyId => $value) {
                // Skip null, empty string, or empty arrays
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    continue;
                }

                // For arrays, filter out empty values and encode
                if (is_array($value)) {
                    $value = array_values(array_filter($value, function($v) {
                        return $v !== null && $v !== '';
                    }));

                    // Skip if array is empty after filtering
                    if (empty($value)) {
                        continue;
                    }

                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $product->propertyValues()->create([
                    'property_id' => $propertyId,
                    'value' => $value,
                ]);
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

        return response()->json($product->load(['variants', 'propertyValues.property']));
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
