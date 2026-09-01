<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Shop;

use HolartWeb\AxoraCMS\Models\Shop\TFilter;
use HolartWeb\AxoraCMS\Models\Shop\TFilterValue;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FilterController extends Controller
{
    /**
     * Get all filters with optional catalog filter
     */
    public function index(Request $request): JsonResponse
    {
        $query = TFilter::with(['values', 'catalog']);

        // Filter by catalog (null for global filters)
        if ($request->has('catalog_id')) {
            if ($request->get('catalog_id') === 'global') {
                $query->whereNull('catalog_id');
            } else {
                $query->where('catalog_id', $request->get('catalog_id'));
            }
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $filters = $query->orderBy('sort')->orderBy('name')->get();

        return response()->json($filters);
    }

    /**
     * Get filters available for a specific catalog (includes global and parent catalogs)
     */
    public function forCatalog($catalogId): JsonResponse
    {
        $filters = TFilter::with(['values' => function ($query) {
            $query->where('is_active', true)->orderBy('sort');
        }])
            ->forCatalog($catalogId)
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return response()->json($filters);
    }

    /**
     * Get single filter with values
     */
    public function show($id): JsonResponse
    {
        $filter = TFilter::with(['values', 'catalog'])->findOrFail($id);

        return response()->json($filter);
    }

    /**
     * Create new filter
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:t_filters,code',
            'type' => 'required|in:select,checkbox,range,entity,string',
            'catalog_id' => 'nullable|exists:t_catalogs,id',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*.value' => 'required_unless:type,range|string',
            'values.*.code' => 'nullable|string',
            'values.*.sort' => 'nullable|integer',
            'values.*.is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = TFilter::generateCode($validated['name']);
        }

        // Extract values
        $values = $validated['values'] ?? [];
        unset($validated['values']);

        $filter = TFilter::create($validated);

        // Create filter values (skip for range type as they don't need predefined values)
        if ($filter->type !== 'range' && $filter->type !== 'entity' && $filter->type !== 'string') {
            foreach ($values as $valueData) {
                $filter->values()->create($valueData);
            }
        }

        // Log activity
        $filterType = $filter->catalog_id ? 'категорийный' : 'глобальный';
        TAdminAction::log('created', 'filter', $filter->id,
            'Создан '.$filterType.' фильтр "'.$filter->name.'"');

        return response()->json($filter->load('values'), 201);
    }

    /**
     * Update filter
     */
    public function update(Request $request, $id): JsonResponse
    {
        $filter = TFilter::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:t_filters,code,'.$id,
            'type' => 'required|in:select,checkbox,range,entity,string',
            'catalog_id' => 'nullable|exists:t_catalogs,id',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*.id' => 'nullable|integer',
            'values.*.value' => 'required_unless:type,range,entity,string|string',
            'values.*.code' => 'nullable|string',
            'values.*.sort' => 'nullable|integer',
            'values.*.is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Handle values update if provided.
        //
        // We must NOT wipe and recreate every value: product ↔ value assignments
        // in t_product_filter_values reference t_filter_values.id, so recreating
        // values with fresh IDs would drop the filter from every product that used
        // it. Instead we update existing values in place (keeping their IDs),
        // create only the new ones, and delete only the values that were actually
        // removed. The FK cascade then clears assignments for removed values only.
        if (array_key_exists('values', $validated)) {
            $values = $validated['values'] ?? [];
            unset($validated['values']);

            $valuelessType = in_array($filter->type, ['range', 'entity', 'string'], true);

            if ($valuelessType) {
                $filter->values()->delete();
            } else {
                $keepIds = [];

                foreach ($values as $valueData) {
                    $payload = [
                        'value' => $valueData['value'] ?? '',
                        'code' => $valueData['code'] ?? null,
                        'sort' => $valueData['sort'] ?? 0,
                        'is_active' => $valueData['is_active'] ?? true,
                    ];

                    $existing = ! empty($valueData['id'])
                        ? $filter->values()->whereKey($valueData['id'])->first()
                        : null;

                    if ($existing) {
                        $existing->update($payload);
                        $keepIds[] = $existing->id;
                    } else {
                        $keepIds[] = $filter->values()->create($payload)->id;
                    }
                }

                $filter->values()->whereNotIn('id', $keepIds)->delete();
            }
        }

        $oldData = $filter->getOriginal();
        $filter->update($validated);

        // Log activity
        $filterType = $filter->catalog_id ? 'категорийный' : 'глобальный';
        TAdminAction::log('updated', 'filter', $filter->id,
            'Обновлен '.$filterType.' фильтр "'.$filter->name.'"', [
                'old' => $oldData,
                'new' => $filter->getAttributes(),
            ]);

        return response()->json($filter->load('values'));
    }

    /**
     * Delete filter
     */
    public function destroy($id): JsonResponse
    {
        $filter = TFilter::findOrFail($id);
        $filterName = $filter->name;
        $filterType = $filter->catalog_id ? 'категорийный' : 'глобальный';

        $filter->delete();

        // Log activity
        TAdminAction::log('deleted', 'filter', $id,
            'Удален '.$filterType.' фильтр "'.$filterName.'"');

        return response()->json(['message' => 'Фильтр удален']);
    }

    /**
     * Add value to filter
     */
    public function addValue(Request $request, $id): JsonResponse
    {
        $filter = TFilter::findOrFail($id);

        $validated = $request->validate([
            'value' => 'required|string',
            'code' => 'nullable|string',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $filterValue = $filter->values()->create($validated);

        // Log activity
        TAdminAction::log('created', 'filter_value', $filterValue->id,
            'Добавлено значение "'.$filterValue->value.'" в фильтр "'.$filter->name.'"');

        return response()->json($filterValue, 201);
    }

    /**
     * Update filter value
     */
    public function updateValue(Request $request, $filterId, $valueId): JsonResponse
    {
        $filter = TFilter::findOrFail($filterId);
        $filterValue = TFilterValue::where('filter_id', $filterId)
            ->where('id', $valueId)
            ->firstOrFail();

        $validated = $request->validate([
            'value' => 'required|string',
            'code' => 'nullable|string',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $oldData = $filterValue->getOriginal();
        $filterValue->update($validated);

        // Log activity
        TAdminAction::log('updated', 'filter_value', $filterValue->id,
            'Обновлено значение фильтра "'.$filter->name.'"', [
                'old' => $oldData,
                'new' => $filterValue->getAttributes(),
            ]);

        return response()->json($filterValue);
    }

    /**
     * Delete filter value
     */
    public function deleteValue($filterId, $valueId): JsonResponse
    {
        $filter = TFilter::findOrFail($filterId);
        $filterValue = TFilterValue::where('filter_id', $filterId)
            ->where('id', $valueId)
            ->firstOrFail();

        $valueName = $filterValue->value;
        $filterValue->delete();

        // Log activity
        TAdminAction::log('deleted', 'filter_value', $valueId,
            'Удалено значение "'.$valueName.'" из фильтра "'.$filter->name.'"');

        return response()->json(['message' => 'Значение удалено']);
    }

    /**
     * Generate unique code
     */
    public function generateCode(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $code = TFilter::generateCode(
            $request->input('name'),
            $request->input('exclude_id')
        );

        return response()->json(['code' => $code]);
    }
}
