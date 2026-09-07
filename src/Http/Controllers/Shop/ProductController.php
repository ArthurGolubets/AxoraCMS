<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Shop;

use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\Shop\TProductRelated;
use HolartWeb\AxoraCMS\Models\Shop\TProductVariant;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Models\TModule;
use HolartWeb\AxoraCMS\Models\TPanelSettings;
use HolartWeb\AxoraCMS\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Whether the CommerceML stock integration is available.
     */
    protected function hasStockIntegration(): bool
    {
        return TModule::isInstalled('commerceml');
    }

    /**
     * Whether admins are allowed to edit the stock (quantity) manually.
     * Requires the integration to be present and the site setting to be enabled.
     */
    protected function stockEditingEnabled(): bool
    {
        return $this->hasStockIntegration()
            && Schema::hasColumn('t_products', 'quantity')
            && (bool) TPanelSettings::get('can_edit_product_stock', false);
    }

    /**
     * Integration / stock block for a product, consumed by the
     * "Интеграция и остатки" tab of the product form.
     *
     * @return array{commerceml_installed: bool, can_edit_stock: bool, onec_id: ?string, quantity: ?int}
     */
    protected function integrationPayload(TProduct $product): array
    {
        $installed = $this->hasStockIntegration();

        return [
            'commerceml_installed' => $installed,
            'can_edit_stock' => $this->stockEditingEnabled(),
            'onec_id' => $installed ? $product->getAttribute('1c_id') : null,
            'quantity' => $installed ? (int) $product->getAttribute('quantity') : null,
        ];
    }

    /**
     * Return a SKU that is unique within the given product.
     *
     * A variant SKU only needs to be unique per product, so the same SKU can be
     * reused across different products. On a real collision within one product
     * the SKU gets a "_n" suffix (n = repetitions + 1).
     *
     * @param  array<string, bool>  $taken  SKUs already assigned during this save
     */
    protected function uniqueVariantSku(int $productId, string $sku, array &$taken): string
    {
        $base = trim($sku);
        $candidate = $base;
        $n = 1;

        while (
            isset($taken[$candidate])
            || TProductVariant::where('product_id', $productId)->where('sku', $candidate)->exists()
        ) {
            $n++;
            $candidate = $base.'_'.$n;
        }

        $taken[$candidate] = true;

        return $candidate;
    }

    /**
     * Lightweight flags for the stock/CommerceML integration, used to decide
     * whether to show the "Остаток" column / badge in product lists and the tree.
     */
    public function stockMeta(): JsonResponse
    {
        return response()->json([
            'commerceml_installed' => $this->hasStockIntegration(),
            'can_edit_stock' => $this->stockEditingEnabled(),
        ]);
    }

    /**
     * Replace the companion-product links for a product at a given scope
     * (product level when $variantSku is null, otherwise that variant).
     *
     * @param  array<int, array{related_product_id: int, related_variant_sku?: ?string, sort?: ?int}>  $links
     */
    protected function syncRelatedProducts(TProduct $product, ?string $variantSku, array $links): void
    {
        $relatedClass = 'HolartWeb\AxoraCMS\Models\Shop\TProductRelated';
        if (! class_exists($relatedClass)) {
            return;
        }

        if ($variantSku !== null) {
            $relatedClass::where('product_id', $product->id)
                ->where('variant_sku', $variantSku)
                ->delete();
        }

        $seen = [];

        foreach ($links as $link) {
            $relatedProductId = (int) ($link['related_product_id'] ?? 0);
            if ($relatedProductId <= 0) {
                continue;
            }

            $relatedVariantSku = $link['related_variant_sku'] ?? null;
            $dedupeKey = $relatedProductId.'|'.($relatedVariantSku ?? '');
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $relatedClass::create([
                'product_id' => $product->id,
                'variant_sku' => $variantSku,
                'related_product_id' => $relatedProductId,
                'related_variant_sku' => $relatedVariantSku ?: null,
                'sort' => (int) ($link['sort'] ?? 500),
            ]);
        }
    }

    /**
     * Companion-product links for a product, shaped for the admin form.
     *
     * @return array{product: array<int, array<string, mixed>>, variants: array<string, array<int, array<string, mixed>>>}
     */
    protected function relatedProductsPayload(TProduct $product): array
    {
        $relatedClass = 'HolartWeb\AxoraCMS\Models\Shop\TProductRelated';
        $result = ['product' => [], 'variants' => []];

        if (! class_exists($relatedClass)) {
            return $result;
        }

        $links = $relatedClass::where('product_id', $product->id)
            ->with('relatedProduct:id,name,sku,main_image,price,catalog_id')
            ->orderBy('sort')
            ->get();

        foreach ($links as $link) {
            if (! $link->relatedProduct) {
                continue;
            }

            $row = [
                'related_product_id' => $link->related_product_id,
                'related_variant_sku' => $link->related_variant_sku,
                'sort' => $link->sort,
                'related_product' => [
                    'id' => $link->relatedProduct->id,
                    'name' => $link->relatedProduct->name,
                    'sku' => $link->relatedProduct->sku,
                    'main_image' => $link->relatedProduct->main_image,
                    'price' => $link->relatedProduct->price,
                ],
            ];

            if ($link->variant_sku === null) {
                $result['product'][] = $row;
            } else {
                $result['variants'][$link->variant_sku][] = $row;
            }
        }

        return $result;
    }

    /**
     * Get all products with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = TProduct::with(['catalog', 'variants']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
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

        $perPage = (int) $request->get('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Get single product with filters
     */
    public function show($id): JsonResponse
    {
        $product = TProduct::with(['catalog', 'variants.propertyValues.property', 'propertyValues.property'])->findOrFail($id);

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
            $availableProperties = $product->catalog->getAllProperties()->map(function ($prop) use ($product) {
                $prop->is_inherited = $prop->catalog_id !== $product->catalog_id;

                return $prop;
            });
        }

        // Get property values - format for Vue: {property_id: value}
        $propertyValuesFormatted = [];

        \Log::info('Product propertyValues count: '.$product->propertyValues->count());

        foreach ($product->propertyValues as $pv) {
            \Log::info('PropertyValue:', [
                'id' => $pv->id,
                'product_id' => $pv->product_id,
                'property_id' => $pv->property_id,
                'value' => $pv->value,
            ]);

            $value = $pv->value;
            $decoded = json_decode($value, true);
            $jsonError = json_last_error();

            if ($jsonError === JSON_ERROR_NONE && is_array($decoded)) {
                $propertyValuesFormatted[$pv->property_id] = $decoded;
            } else {
                $propertyValuesFormatted[$pv->property_id] = $value;
            }
        }

        \Log::info('Formatted property values:', $propertyValuesFormatted);

        $response = [
            'product' => $product,
            'available_filters' => $availableFilters,
            'assigned_filters' => $assignedFilters,
            'available_properties' => $availableProperties,
            'property_values' => $propertyValuesFormatted,
            'string_filter_values' => $product->string_filter_values ?? [],
            'integration' => $this->integrationPayload($product),
            'related_products' => $this->relatedProductsPayload($product),
        ];

        \Log::info('Full API response property_values:', $response['property_values']);

        return response()->json($response);
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
            'variants.*.sku' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.old_price' => 'nullable|numeric|min:0',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.image' => 'nullable|string',
            'variants.*.description' => 'nullable|string',
            'variants.*.addition_info' => 'nullable|array',
            'variants.*.property_values' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'filter_values.*' => 'exists:t_filter_values,id',
            'range_filter_values' => 'nullable|array',
            'range_filter_values.*' => 'numeric',
            'addition_info' => 'nullable|array',
            'property_values' => 'nullable|array',
            'entity_filter_values' => 'nullable|array',
            'entity_filter_values.*' => 'nullable|integer',
            'string_filter_values' => 'nullable|array',
            'string_filter_values.*' => 'nullable|string',
            'related_products' => 'nullable|array',
            'related_products.*.related_product_id' => 'required|integer|exists:t_products,id',
            'related_products.*.related_variant_sku' => 'nullable|string',
            'related_products.*.sort' => 'nullable|integer',
            'variants.*.related_products' => 'nullable|array',
            'variants.*.related_products.*.related_product_id' => 'required|integer|exists:t_products,id',
            'variants.*.related_products.*.related_variant_sku' => 'nullable|string',
            'variants.*.related_products.*.sort' => 'nullable|integer',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = TProduct::generateSlug($validated['name']);
        }

        // Rich-text content is untrusted (rich editor, API, 1C import) — sanitize.
        if (array_key_exists('content', $validated)) {
            $validated['content'] = HtmlSanitizer::clean($validated['content']);
        }

        // Extract variants data
        $variants = $validated['variants'] ?? [];
        unset($validated['variants']);

        // Extract filter values
        $filterValues = $validated['filter_values'] ?? [];
        unset($validated['filter_values']);

        // Extract range filter values
        $rangeFilterValues = $validated['range_filter_values'] ?? [];
        unset($validated['range_filter_values']);

        // Extract property values
        $propertyValues = $validated['property_values'] ?? [];
        unset($validated['property_values']);

        // Extract companion products (product level)
        $relatedProducts = $validated['related_products'] ?? null;
        unset($validated['related_products']);

        $product = TProduct::create($validated);

        // Create variants if provided
        $variantSkus = [];
        foreach ($variants as $variantData) {
            // Extract property values for variant
            $variantPropertyValues = $variantData['property_values'] ?? [];
            unset($variantData['property_values']);

            // Extract companion products for this variant
            $variantRelated = $variantData['related_products'] ?? null;
            unset($variantData['related_products']);

            // SKU only has to be unique within this product
            $variantData['sku'] = $this->uniqueVariantSku($product->id, $variantData['sku'] ?? '', $variantSkus);

            // Create variant
            $variant = $product->variants()->create($variantData);

            if ($variantRelated !== null) {
                $this->syncRelatedProducts($product, $variant->sku, $variantRelated);
            }

            // Save variant property values if provided
            if (! empty($variantPropertyValues) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductVariantPropertyValue')) {
                foreach ($variantPropertyValues as $propertyId => $value) {
                    // Skip null, empty string, or empty arrays
                    if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                        continue;
                    }

                    // For arrays, filter out empty values and encode
                    if (is_array($value)) {
                        $value = array_values(array_filter($value, function ($v) {
                            return $v !== null && $v !== '';
                        }));

                        // Skip if array is empty after filtering
                        if (empty($value)) {
                            continue;
                        }

                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }

                    $variant->propertyValues()->create([
                        'property_id' => $propertyId,
                        'value' => $value,
                    ]);
                }
            }
        }

        // Sync filter values if provided
        if (! empty($filterValues) && method_exists($product, 'syncFilterValues')) {
            $product->syncFilterValues($filterValues);
        }

        // Sync product-level companion products
        if ($relatedProducts !== null) {
            $this->syncRelatedProducts($product, null, $relatedProducts);
        }

        // Save property values if provided
        if (! empty($propertyValues) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductPropertyValue')) {
            \Log::info('Saving property values for product', [
                'product_id' => $product->id,
                'property_values' => $propertyValues,
            ]);

            foreach ($propertyValues as $propertyId => $value) {
                \Log::info('Processing property', [
                    'property_id' => $propertyId,
                    'value' => $value,
                    'is_array' => is_array($value),
                ]);

                // Skip null, empty string, or empty arrays
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    \Log::info('Skipping empty value for property', ['property_id' => $propertyId]);

                    continue;
                }

                // For arrays, filter out empty values and encode
                if (is_array($value)) {
                    $value = array_values(array_filter($value, function ($v) {
                        return $v !== null && $v !== '';
                    }));

                    // Skip if array is empty after filtering
                    if (empty($value)) {
                        \Log::info('Skipping empty array after filtering for property', ['property_id' => $propertyId]);

                        continue;
                    }

                    \Log::info('Encoding array to JSON', ['property_id' => $propertyId, 'values' => $value]);
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                \Log::info('Creating property value', [
                    'property_id' => $propertyId,
                    'final_value' => $value,
                ]);

                $product->propertyValues()->create([
                    'property_id' => $propertyId,
                    'value' => $value,
                ]);
            }
        }

        // Log activity
        TAdminAction::log('created', 'product', $product->id,
            'Создан товар "'.$product->name.'" (SKU: '.$product->sku.')');

        return response()->json($product->load(['variants', 'propertyValues.property']), 201);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = TProduct::findOrFail($id);

        $validated = $request->validate([
            'catalog_id' => 'sometimes|required|exists:t_catalogs,id',
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:t_products,slug,'.$id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'sku' => 'sometimes|required|string|unique:t_products,sku,'.$id,
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
            'variants.*.sku' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.old_price' => 'nullable|numeric|min:0',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.image' => 'nullable|string',
            'variants.*.description' => 'nullable|string',
            'variants.*.addition_info' => 'nullable|array',
            'variants.*.property_values' => 'nullable|array',
            'filter_values' => 'nullable|array',
            'filter_values.*' => 'exists:t_filter_values,id',
            'range_filter_values' => 'nullable|array',
            'range_filter_values.*' => 'numeric',
            'addition_info' => 'nullable|array',
            'property_values' => 'nullable|array',
            'entity_filter_values' => 'nullable|array',
            'entity_filter_values.*' => 'nullable|integer',
            'string_filter_values' => 'nullable|array',
            'string_filter_values.*' => 'nullable|string',
            'related_products' => 'nullable|array',
            'related_products.*.related_product_id' => 'required|integer|exists:t_products,id',
            'related_products.*.related_variant_sku' => 'nullable|string',
            'related_products.*.sort' => 'nullable|integer',
            'variants.*.related_products' => 'nullable|array',
            'variants.*.related_products.*.related_product_id' => 'required|integer|exists:t_products,id',
            'variants.*.related_products.*.related_variant_sku' => 'nullable|string',
            'variants.*.related_products.*.sort' => 'nullable|integer',
        ]);

        // Rich-text content is untrusted (rich editor, API, 1C import) — sanitize.
        if (array_key_exists('content', $validated)) {
            $validated['content'] = HtmlSanitizer::clean($validated['content']);
        }

        // Stock (quantity) is only writable when the CommerceML integration is
        // installed and the "Можно редактировать остаток" site setting is on.
        // The 1C identifier is never writable here — it is owned by the 1C sync.
        if ($this->stockEditingEnabled() && $request->has('quantity')) {
            $validated['quantity'] = $request->validate([
                'quantity' => 'nullable|integer|min:0',
            ])['quantity'] ?? 0;
        }

        // Companion products (product level) — synced after variants so variant
        // SKUs referenced by variant-scoped links exist.
        $relatedProducts = $validated['related_products'] ?? null;
        unset($validated['related_products']);

        // Handle variants update
        if (isset($validated['variants'])) {
            $variants = $validated['variants'];
            unset($validated['variants']);

            // Variant-scoped companion links are keyed by SKU and rebuilt from the
            // payload, so drop the current ones before recreating variants.
            if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductRelated')) {
                TProductRelated::where('product_id', $product->id)
                    ->whereNotNull('variant_sku')
                    ->delete();
            }

            // Delete old variants (cascade will delete property values)
            $product->variants()->delete();

            // Create new variants
            $variantSkus = [];
            foreach ($variants as $variantData) {
                // Extract property values for variant
                $variantPropertyValues = $variantData['property_values'] ?? [];
                unset($variantData['property_values']);

                // Extract companion products for this variant
                $variantRelated = $variantData['related_products'] ?? null;
                unset($variantData['related_products']);

                // SKU only has to be unique within this product
                $variantData['sku'] = $this->uniqueVariantSku($product->id, $variantData['sku'] ?? '', $variantSkus);

                // Create variant
                $variant = $product->variants()->create($variantData);

                if ($variantRelated !== null) {
                    $this->syncRelatedProducts($product, $variant->sku, $variantRelated);
                }

                // Save variant property values if provided
                if (! empty($variantPropertyValues) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductVariantPropertyValue')) {
                    \Log::info('Saving variant property values', [
                        'variant_id' => $variant->id,
                        'property_values' => $variantPropertyValues,
                    ]);

                    foreach ($variantPropertyValues as $propertyId => $value) {
                        // Skip null, empty string, or empty arrays
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            continue;
                        }

                        // For arrays, filter out empty values and encode
                        if (is_array($value)) {
                            $value = array_values(array_filter($value, function ($v) {
                                return $v !== null && $v !== '';
                            }));

                            // Skip if array is empty after filtering
                            if (empty($value)) {
                                continue;
                            }

                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                        }

                        $variant->propertyValues()->create([
                            'property_id' => $propertyId,
                            'value' => $value,
                        ]);
                    }
                }
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

        // Sync product-level companion products
        if ($relatedProducts !== null && class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductRelated')) {
            TProductRelated::where('product_id', $product->id)
                ->whereNull('variant_sku')
                ->delete();
            $this->syncRelatedProducts($product, null, $relatedProducts);
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
                    $value = array_values(array_filter($value, function ($v) {
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
            'Обновлен товар "'.$product->name.'" (SKU: '.$product->sku.')', [
                'old' => $oldData,
                'new' => $product->getAttributes(),
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
            'Удален товар "'.$productName.'" (SKU: '.$productSku.')');

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
            'Массовое удаление товаров (количество: '.$count.')');

        return response()->json(['message' => 'Товары удалены']);
    }

    /**
     * Search products for variant creation
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $excludeId = $request->get('exclude_id');

        // Explicit id lookup: used to re-hydrate line items already on an order
        // (e.g. the order edit form) without preloading the whole catalog.
        $ids = array_filter(array_map('intval', explode(',', (string) $request->get('ids', ''))));

        if (empty($ids) && strlen($query) < 2) {
            return response()->json(['products' => []]);
        }

        $products = TProduct::when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->when(empty($ids), function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                })->where('is_active', true);
            })
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with(['catalog', 'variants:id,product_id,name,sku,price'])
            ->limit(! empty($ids) ? 50 : 20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'old_price' => $product->old_price,
                    'main_image' => $product->main_image,
                    'description' => $product->description,
                    'addition_info' => $product->addition_info,
                    'property_values' => $product->propertyValues->pluck('value', 'property_id')->toArray(),
                    'catalog_name' => $product->catalog->name ?? null,
                    'variants' => $product->variants->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'sku' => $v->sku,
                        'price' => $v->price,
                    ]),
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * Create a variant on a product using another product as the source.
     *
     * Used by the "create duplicate variant" option: when product A adds
     * product B as a variant, this endpoint mirrors the relation by adding
     * product A as a variant of product B. Idempotent by generated SKU.
     */
    public function createVariantFromProduct(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'based_on_product_id' => 'required|exists:t_products,id',
        ]);

        $target = TProduct::findOrFail($id);
        $source = TProduct::with('propertyValues')->findOrFail($validated['based_on_product_id']);

        if ($target->id === $source->id) {
            return response()->json(['message' => 'Нельзя создать вариант товара на основании его самого'], 422);
        }

        $variantSku = $source->sku.'-variant';

        $existing = $target->variants()->where('sku', $variantSku)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Вариант уже существует',
                'variant' => $existing,
                'created' => false,
            ]);
        }

        // SKU only has to be unique within the target product
        $taken = [];
        $variantSku = $this->uniqueVariantSku($target->id, $variantSku, $taken);

        $variant = $target->variants()->create([
            'name' => $source->name,
            'sku' => $variantSku,
            'price' => $source->price,
            'old_price' => $source->old_price,
            'attributes' => [],
            'image' => $source->main_image ?: '',
            'description' => $source->description ?: '',
            'addition_info' => is_array($source->addition_info) ? $source->addition_info : [],
        ]);

        // Mirror the source product's property values onto the new variant
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TProductVariantPropertyValue')) {
            foreach ($source->propertyValues as $propertyValue) {
                $variant->propertyValues()->create([
                    'property_id' => $propertyValue->property_id,
                    'value' => $propertyValue->value,
                ]);
            }
        }

        TAdminAction::log('created', 'product_variant', $variant->id,
            'Создан дубль-вариант "'.$variant->name.'" в товаре "'.$target->name.'" (SKU: '.$target->sku.')');

        return response()->json([
            'message' => 'Вариант создан',
            'variant' => $variant->load('propertyValues'),
            'created' => true,
        ], 201);
    }

    /**
     * Deactivate product
     */
    public function deactivate($id): JsonResponse
    {
        $product = TProduct::findOrFail($id);
        $product->update(['is_active' => false]);

        // Log activity
        TAdminAction::log('updated', 'product', $product->id,
            'Деактивирован товар "'.$product->name.'" (SKU: '.$product->sku.')');

        return response()->json(['message' => 'Товар деактивирован']);
    }
}
