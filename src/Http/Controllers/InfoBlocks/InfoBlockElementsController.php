<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\InfoBlocks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockElement;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class InfoBlockElementsController extends Controller
{
    /**
     * Get all elements for info block
     */
    public function index(Request $request, $infoBlockId)
    {
        $infoBlock = TInfoBlock::with('fields')->findOrFail($infoBlockId);

        $query = $infoBlock->elements();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $elements = $query->orderBy('sort')->orderBy('id', 'desc')->paginate(20);

        return response()->json($elements);
    }

    /**
     * Get single element
     */
    public function show($infoBlockId, $id)
    {
        $element = TInfoBlockElement::where('info_block_id', $infoBlockId)
            ->with('infoBlock.fields')
            ->findOrFail($id);

        return response()->json($element);
    }

    /**
     * Create new element
     */
    public function store(Request $request, $infoBlockId)
    {
        $infoBlock = TInfoBlock::with('fields')->findOrFail($infoBlockId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|regex:/^[a-z0-9_]+$/',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
            'sort' => 'nullable|integer',
            'properties' => 'nullable|array',
        ]);

        // Validate properties against fields
        $properties = $validated['properties'] ?? [];
        foreach ($infoBlock->fields as $field) {
            if ($field->is_required && (
                    !isset($properties[$field->code]) ||
                    $properties[$field->code] === '' ||
                    $properties[$field->code] === null
                )) {
                return response()->json([
                    'message' => 'Поле "' . $field->name . '" обязательно для заполнения'
                ], 422);
            }

            if (isset($properties[$field->code]) && !$field->validateValue($properties[$field->code])) {
                return response()->json([
                    'message' => 'Неверное значение для поля "' . $field->name . '"'
                ], 422);
            }
        }

        $element = $infoBlock->createElement($validated);

        // Log activity
        TAdminAction::log('created', 'info_block_element', $element->id,
            'Создан элемент "' . $element->name . '" в инфоблоке: ' . $infoBlock->name);

        return response()->json($element->load('infoBlock.fields'), 201);
    }

    /**
     * Update element
     */
    public function update(Request $request, $infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::with('fields')->findOrFail($infoBlockId);
        $element = TInfoBlockElement::where('info_block_id', $infoBlockId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|regex:/^[a-z0-9_]+$/',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
            'sort' => 'nullable|integer',
            'properties' => 'nullable|array',
        ]);

        // Validate properties against fields
        $properties = $validated['properties'] ?? [];
        foreach ($infoBlock->fields as $field) {
            if ($field->is_required && (
                    !isset($properties[$field->code]) ||
                    $properties[$field->code] === '' ||
                    $properties[$field->code] === null
                )) {
                return response()->json([
                    'message' => 'Поле "' . $field->name . '" обязательно для заполнения'
                ], 422);
            }

            if (isset($properties[$field->code]) && !$field->validateValue($properties[$field->code])) {
                return response()->json([
                    'message' => 'Неверное значение для поля "' . $field->name . '"'
                ], 422);
            }
        }

        $oldData = $element->getOriginal();
        $element->update($validated);

        // Log activity
        TAdminAction::log('updated', 'info_block_element', $element->id,
            'Обновлен элемент "' . $element->name . '" в инфоблоке: ' . $infoBlock->name, [
            'old' => $oldData,
            'new' => $element->getAttributes()
        ]);

        return response()->json($element->load('infoBlock.fields'));
    }

    /**
     * Delete element
     */
    public function destroy($infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $element = TInfoBlockElement::where('info_block_id', $infoBlockId)->findOrFail($id);
        $elementName = $element->name;

        $element->delete();

        // Log activity
        TAdminAction::log('deleted', 'info_block_element', $id,
            'Удален элемент "' . $elementName . '" из инфоблока: ' . $infoBlock->name);

        return response()->json(['message' => 'Элемент удален']);
    }
}
