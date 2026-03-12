<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use HolartWeb\HolartCMS\Models\Shop\TCharacteristicDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CharacteristicDefinitionsController extends Controller
{
    /**
     * Get all characteristic definitions
     */
    public function index(Request $request)
    {
        $query = TCharacteristicDefinition::query();

        // Filter by applies_to if provided
        if ($request->has('applies_to') && in_array($request->applies_to, ['catalog', 'product'])) {
            $query->whereIn('applies_to', [$request->applies_to, 'both']);
        }

        $definitions = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($definitions);
    }

    /**
     * Get single characteristic definition
     */
    public function show($id)
    {
        $definition = TCharacteristicDefinition::findOrFail($id);
        return response()->json($definition);
    }

    /**
     * Create new characteristic definition
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:t_characteristic_definitions,code',
            'type' => 'required|in:string,number,boolean',
            'multiple' => 'boolean',
            'applies_to' => 'required|in:catalog,product,both',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = TCharacteristicDefinition::generateCode($data['name']);
        }

        // Ensure boolean type is not multiple
        if ($data['type'] === 'boolean') {
            $data['multiple'] = false;
        }

        $definition = TCharacteristicDefinition::create($data);

        return response()->json($definition, 201);
    }

    /**
     * Update characteristic definition
     */
    public function update(Request $request, $id)
    {
        $definition = TCharacteristicDefinition::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:t_characteristic_definitions,code,' . $id,
            'type' => 'sometimes|required|in:string,number,boolean',
            'multiple' => 'boolean',
            'applies_to' => 'sometimes|required|in:catalog,product,both',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        // Ensure boolean type is not multiple
        if (isset($data['type']) && $data['type'] === 'boolean') {
            $data['multiple'] = false;
        }

        $definition->update($data);

        return response()->json($definition);
    }

    /**
     * Delete characteristic definition
     */
    public function destroy($id)
    {
        $definition = TCharacteristicDefinition::findOrFail($id);
        $definition->delete();

        return response()->json(['message' => 'Characteristic definition deleted successfully']);
    }

    /**
     * Generate code from name
     */
    public function generateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'current_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $name = $request->input('name');
        $currentId = $request->input('current_id');

        $code = TCharacteristicDefinition::generateCode($name);

        // If we're editing an existing definition, check if the generated code matches the current one
        if ($currentId) {
            $current = TCharacteristicDefinition::find($currentId);
            if ($current && $current->code === $code) {
                return response()->json(['code' => $code]);
            }
        }

        return response()->json(['code' => $code]);
    }

    /**
     * Reorder characteristic definitions
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:t_characteristic_definitions,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->input('items') as $item) {
            TCharacteristicDefinition::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
