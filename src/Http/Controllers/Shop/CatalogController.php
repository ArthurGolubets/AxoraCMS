<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Shop;

use HolartWeb\AxoraCMS\Models\Shop\TCatalog;
use HolartWeb\AxoraCMS\Models\Shop\TCatalogPropertyGroup;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use HolartWeb\AxoraCMS\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    /**
     * Supported catalog property value types.
     */
    protected const PROPERTY_TYPES = ['string', 'text', 'number', 'color', 'image', 'table', 'entity'];

    /**
     * Property types that support the "is_multiple" flag.
     */
    protected const MULTIPLE_CAPABLE_PROPERTY_TYPES = ['string', 'text', 'number', 'color', 'image', 'entity'];

    /**
     * Build a persistable attribute set for a catalog property from raw request input,
     * clamping the type to a known value and keeping "settings" consistent with it.
     *
     * @param  array<string, mixed>  $property
     * @param  int|string|null  $groupId
     * @return array<string, mixed>
     */
    protected function propertyAttributes(array $property, $groupId): array
    {
        $type = in_array($property['type'] ?? null, self::PROPERTY_TYPES, true)
            ? $property['type']
            : 'string';

        $settings = is_array($property['settings'] ?? null) ? $property['settings'] : [];

        if ($type === 'entity') {
            $settings['entity_types'] = array_values(array_intersect(
                $settings['entity_types'] ?? ['product', 'catalog', 'infoblock'],
                ['product', 'catalog', 'infoblock']
            )) ?: ['product', 'catalog', 'infoblock'];
        } else {
            unset($settings['entity_types'], $settings['infoblock_id']);
        }

        if ($type !== 'table') {
            unset($settings['table']);
        }

        return [
            'code' => $property['code'],
            'name' => $property['name'],
            'type' => $type,
            'settings' => $settings ?: null,
            'is_multiple' => in_array($type, self::MULTIPLE_CAPABLE_PROPERTY_TYPES, true)
                ? (bool) ($property['is_multiple'] ?? false)
                : false,
            'sort_order' => $property['sort_order'] ?? 500,
            'group_id' => $groupId,
        ];
    }

    /**
     * Get all catalogs with hierarchy
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');

        if ($search) {
            $catalogs = TCatalog::where('name', 'like', "%{$search}%")
                ->orWhereHas('products', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })
                ->with(['children', 'products' => function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->limit(50);
                }])
                ->withCount(['children', 'products'])
                ->limit(100)
                ->get();
        } else {
            // Category tree only: counts, no nested product payloads.
            // Products for a category are loaded lazily on the frontend
            // via catalogs/{id} / catalogs/{id}/children.
            $catalogs = TCatalog::whereNull('parent_id')
                ->withCount(['children', 'products'])
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
        $catalog = TCatalog::with(['parent', 'children', 'products.variants', 'properties'])
            ->findOrFail($id);

        // Get filters for this catalog
        $filters = [];
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TFilter')) {
            $filterClass = 'HolartWeb\AxoraCMS\Models\Shop\TFilter';
            $filters = $filterClass::with('values')
                ->where('catalog_id', $id)
                ->orderBy('sort')
                ->orderBy('name')
                ->get();
        }

        // Get all properties including inherited
        $allProperties = $catalog->getAllProperties();
        $ownProperties = $catalog->properties;
        $inheritedProperties = $allProperties->filter(function ($prop) use ($ownProperties) {
            return ! $ownProperties->contains('id', $prop->id);
        })->map(function ($prop) {
            $prop->is_inherited = true;
            if ($prop->catalog) {
                $prop->catalog_name = $prop->catalog->name;
            }

            return $prop;
        });

        // Get property groups for this catalog
        $propertyGroups = TCatalogPropertyGroup::where('catalog_id', $id)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'catalog' => $catalog,
            'breadcrumbs' => $catalog->getBreadcrumbs(),
            'filters' => $filters,
            'properties' => $ownProperties,
            'inherited_properties' => $inheritedProperties,
            'all_properties' => $allProperties,
            'property_groups' => $propertyGroups,
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
            'addition_info' => 'nullable|array',
            'properties' => 'nullable|array',
            'property_groups' => 'nullable|array',
        ]);

        $properties = $validated['properties'] ?? [];
        $propertyGroups = $validated['property_groups'] ?? [];
        unset($validated['properties'], $validated['property_groups']);

        if (array_key_exists('content', $validated)) {
            $validated['content'] = HtmlSanitizer::clean($validated['content']);
        }

        $catalog = TCatalog::create($validated);

        // Create property groups
        $groupIdMap = []; // temp_id => real_id
        foreach ($propertyGroups as $group) {
            if (empty($group['name'])) {
                continue;
            }

            $created = TCatalogPropertyGroup::create([
                'catalog_id' => $catalog->id,
                'code' => $group['code'] ?? Str::slug($group['name'], '_'),
                'name' => $group['name'],
                'sort_order' => $group['sort_order'] ?? 500,
            ]);

            if (isset($group['temp_id'])) {
                $groupIdMap[$group['temp_id']] = $created->id;
            }
        }

        // Create properties
        if (! empty($properties) && class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalogProperty')) {
            foreach ($properties as $property) {
                if (empty($property['code']) || empty($property['name'])) {
                    continue;
                }

                // Resolve group_id: temp_id -> real_id
                $groupId = null;
                if (! empty($property['group_id'])) {
                    $groupId = $groupIdMap[$property['group_id']] ?? $property['group_id'];
                }

                $catalog->properties()->create($this->propertyAttributes($property, $groupId));
            }
        }

        TAdminAction::log('created', 'catalog', $catalog->id,
            'Создана категория "'.$catalog->name.'"');

        return response()->json($catalog->load('properties'), 201);
    }

    /**
     * Update catalog
     */
    public function update(Request $request, $id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_catalogs,id',
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:t_catalogs,slug,'.$id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'image' => 'nullable|string',
            'content' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'addition_info' => 'nullable|array',
            'properties' => 'nullable|array',
            'property_groups' => 'nullable|array',
        ]);

        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return response()->json(['message' => 'Категория не может быть родителем самой себя'], 422);
        }

        $properties = $validated['properties'] ?? null;
        $propertyGroups = $validated['property_groups'] ?? null;
        unset($validated['properties'], $validated['property_groups']);

        if (array_key_exists('content', $validated)) {
            $validated['content'] = HtmlSanitizer::clean($validated['content']);
        }

        $oldData = $catalog->getOriginal();
        $catalog->update($validated);

        // Update property groups
        if ($propertyGroups !== null) {
            $groupIdMap = []; // temp_id => real_id

            $incomingGroupIds = collect($propertyGroups)->pluck('id')->filter();

            // Delete groups not in the incoming list (set null handled by DB onDelete)
            TCatalogPropertyGroup::where('catalog_id', $id)
                ->whereNotIn('id', $incomingGroupIds)
                ->delete();

            foreach ($propertyGroups as $group) {
                if (empty($group['name'])) {
                    continue;
                }

                if (isset($group['id'])) {
                    // Update existing
                    TCatalogPropertyGroup::where('id', $group['id'])
                        ->where('catalog_id', $id)
                        ->update([
                            'code' => $group['code'] ?? Str::slug($group['name'], '_'),
                            'name' => $group['name'],
                            'sort_order' => $group['sort_order'] ?? 500,
                        ]);
                    $groupIdMap[$group['id']] = $group['id'];
                } else {
                    // Create new
                    $created = TCatalogPropertyGroup::create([
                        'catalog_id' => $id,
                        'code' => $group['code'] ?? Str::slug($group['name'], '_'),
                        'name' => $group['name'],
                        'sort_order' => $group['sort_order'] ?? 500,
                    ]);

                    if (isset($group['temp_id'])) {
                        $groupIdMap[$group['temp_id']] = $created->id;
                    }
                }
            }
        }

        // Update properties
        if ($properties !== null && class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalogProperty')) {
            $propertyIds = collect($properties)->pluck('id')->filter();
            $catalog->properties()->whereNotIn('id', $propertyIds)->delete();

            foreach ($properties as $property) {
                if (empty($property['code']) || empty($property['name'])) {
                    continue;
                }

                // Resolve group_id
                $groupId = null;
                if (! empty($property['group_id'])) {
                    $groupId = isset($groupIdMap) ? ($groupIdMap[$property['group_id']] ?? $property['group_id']) : $property['group_id'];
                }

                $existingProperty = isset($property['id'])
                    ? $catalog->properties()->where('id', $property['id'])->first()
                    : null;

                if ($existingProperty) {
                    $existingProperty->update($this->propertyAttributes($property, $groupId));
                } else {
                    $catalog->properties()->create($this->propertyAttributes($property, $groupId));
                }
            }
        }

        TAdminAction::log('updated', 'catalog', $catalog->id,
            'Обновлена категория "'.$catalog->name.'"', [
                'old' => $oldData,
                'new' => $catalog->getAttributes(),
            ]);

        return response()->json($catalog->load('properties'));
    }

    /**
     * Delete catalog
     */
    public function destroy($id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);
        $catalogName = $catalog->name;

        if ($catalog->products()->exists()) {
            return response()->json([
                'message' => 'Невозможно удалить категорию с товарами',
            ], 422);
        }

        if ($catalog->hasChildren()) {
            return response()->json([
                'message' => 'Невозможно удалить категорию с подкатегориями',
            ], 422);
        }

        $catalog->delete();

        TAdminAction::log('deleted', 'catalog', $id,
            'Удалена категория "'.$catalogName.'"');

        return response()->json(['message' => 'Категория удалена']);
    }

    /**
     * Get children of a catalog with full tree structure
     */
    public function children($id): JsonResponse
    {
        $catalog = TCatalog::findOrFail($id);

        $children = $catalog->children()
            ->with(['products'])
            ->withCount(['children', 'products'])
            ->get();

        foreach ($children as $child) {
            $this->loadDescendants($child);
        }

        return response()->json($children);
    }

    /**
     * Recursively load all descendants for a catalog
     */
    private function loadDescendants($catalog): void
    {
        if ($catalog->children_count > 0) {
            $catalog->load(['children' => function ($query) {
                $query->with('products')->withCount(['children', 'products']);
            }]);

            foreach ($catalog->children as $child) {
                $this->loadDescendants($child);
            }
        }
    }
}
