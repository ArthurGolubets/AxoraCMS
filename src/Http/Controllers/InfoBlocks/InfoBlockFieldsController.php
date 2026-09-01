<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\InfoBlocks;

use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockField;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class InfoBlockFieldsController extends Controller
{
    /**
     * Validation rules shared by store() and update().
     *
     * The list of allowed field types is derived from the model so new types
     * (e.g. button, table) only have to be registered in one place.
     */
    protected function fieldRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|regex:/^[a-z0-9_]+$/',
            'type' => ['required', Rule::in(array_keys(TInfoBlockField::getAvailableTypes()))],
            'sort' => 'nullable|integer',
            'is_required' => 'boolean',
            'is_multiple' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }

    /**
     * Get all fields for info block
     */
    public function index($infoBlockId)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $fields = $infoBlock->fields;

        return response()->json($fields);
    }

    /**
     * Get single field
     */
    public function show($infoBlockId, $id)
    {
        $field = TInfoBlockField::where('info_block_id', $infoBlockId)
            ->findOrFail($id);

        return response()->json($field);
    }

    /**
     * Create new field
     */
    public function store(Request $request, $infoBlockId)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);

        $validated = $request->validate($this->fieldRules());

        // Check if code already exists for this info block
        if (TInfoBlockField::where('info_block_id', $infoBlockId)->where('code', $validated['code'])->exists()) {
            return response()->json([
                'message' => 'Поле с таким кодом уже существует',
            ], 422);
        }

        $field = $infoBlock->fields()->create($validated);

        // Log activity
        TAdminAction::log('created', 'info_block_field', $field->id,
            'Создано поле "'.$field->name.'" для инфоблока: '.$infoBlock->name);

        return response()->json($field, 201);
    }

    /**
     * Update field
     */
    public function update(Request $request, $infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $field = TInfoBlockField::where('info_block_id', $infoBlockId)->findOrFail($id);

        $validated = $request->validate($this->fieldRules());

        // Check if code already exists for this info block (exclude current field)
        if (TInfoBlockField::where('info_block_id', $infoBlockId)
            ->where('code', $validated['code'])
            ->where('id', '!=', $id)
            ->exists()) {
            return response()->json([
                'message' => 'Поле с таким кодом уже существует',
            ], 422);
        }

        $oldData = $field->getOriginal();
        $field->update($validated);

        // Log activity
        TAdminAction::log('updated', 'info_block_field', $field->id,
            'Обновлено поле "'.$field->name.'" для инфоблока: '.$infoBlock->name, [
                'old' => $oldData,
                'new' => $field->getAttributes(),
            ]);

        return response()->json($field);
    }

    /**
     * Delete field
     */
    public function destroy($infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $field = TInfoBlockField::where('info_block_id', $infoBlockId)->findOrFail($id);
        $fieldName = $field->name;

        $field->delete();

        // Log activity
        TAdminAction::log('deleted', 'info_block_field', $id,
            'Удалено поле "'.$fieldName.'" из инфоблока: '.$infoBlock->name);

        return response()->json(['message' => 'Поле удалено']);
    }

    /**
     * Get available field types
     */
    public function types()
    {
        return response()->json(TInfoBlockField::getAvailableTypes());
    }
}
