<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\InfoBlocks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlockSection;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class InfoBlockSectionsController extends Controller
{
    /**
     * Get all sections for info block (tree structure)
     */
    public function index($infoBlockId)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);

        if (!$infoBlock->isCatalog()) {
            return response()->json(['message' => 'Инфоблок не является каталогом'], 400);
        }

        $tree = $infoBlock->getSectionsTree();

        // Get elements without section
        $elementsWithoutSection = $infoBlock->elements()
            ->whereNull('section_id')
            ->orderBy('sort')
            ->get(['id', 'name', 'code', 'is_active'])
            ->toArray();

        return response()->json([
            'sections' => $tree,
            'elementsWithoutSection' => $elementsWithoutSection
        ]);
    }

    /**
     * Get flat list of sections for select dropdown
     */
    public function list($infoBlockId)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);

        if (!$infoBlock->isCatalog()) {
            return response()->json(['message' => 'Инфоблок не является каталогом'], 400);
        }

        $sections = $infoBlock->sections()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'parent_id', 'name', 'code']);

        return response()->json($sections);
    }

    /**
     * Get single section
     */
    public function show($infoBlockId, $id)
    {
        $section = TInfoBlockSection::where('info_block_id', $infoBlockId)
            ->with(['parent', 'children'])
            ->findOrFail($id);

        return response()->json($section);
    }

    /**
     * Create new section
     */
    public function store(Request $request, $infoBlockId)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);

        if (!$infoBlock->isCatalog()) {
            return response()->json(['message' => 'Инфоблок не является каталогом'], 400);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_info_block_sections,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|regex:/^[a-z0-9_]+$/',
            'image' => 'nullable|string',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Validate parent belongs to same info block
        if (!empty($validated['parent_id'])) {
            $parent = TInfoBlockSection::find($validated['parent_id']);
            if ($parent->info_block_id !== (int) $infoBlockId) {
                return response()->json(['message' => 'Родительский раздел принадлежит другому инфоблоку'], 422);
            }
        }

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = TInfoBlockSection::generateCode($validated['name'], $infoBlockId);
        }

        $validated['info_block_id'] = $infoBlockId;

        $section = TInfoBlockSection::create($validated);

        // Log activity
        TAdminAction::log('created', 'info_block_section', $section->id,
            'Создан раздел "' . $section->name . '" в инфоблоке: ' . $infoBlock->name);

        return response()->json($section->load(['parent', 'children']), 201);
    }

    /**
     * Update section
     */
    public function update(Request $request, $infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $section = TInfoBlockSection::where('info_block_id', $infoBlockId)->findOrFail($id);

        if (!$infoBlock->isCatalog()) {
            return response()->json(['message' => 'Инфоблок не является каталогом'], 400);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_info_block_sections,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|regex:/^[a-z0-9_]+$/',
            'image' => 'nullable|string',
            'description' => 'nullable|string',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Validate parent belongs to same info block
        if (!empty($validated['parent_id'])) {
            $parent = TInfoBlockSection::find($validated['parent_id']);
            if ($parent->info_block_id !== (int) $infoBlockId) {
                return response()->json(['message' => 'Родительский раздел принадлежит другому инфоблоку'], 422);
            }

            // Check if trying to set itself or its descendant as parent
            if ($validated['parent_id'] === $id) {
                return response()->json(['message' => 'Раздел не может быть родителем самого себя'], 422);
            }

            if ($section->isAncestorOf($parent)) {
                return response()->json(['message' => 'Нельзя установить потомка в качестве родителя'], 422);
            }
        }

        $oldData = $section->getOriginal();
        $section->update($validated);

        // Log activity
        TAdminAction::log('updated', 'info_block_section', $section->id,
            'Обновлен раздел "' . $section->name . '" в инфоблоке: ' . $infoBlock->name, [
            'old' => $oldData,
            'new' => $section->getAttributes()
        ]);

        return response()->json($section->load(['parent', 'children']));
    }

    /**
     * Delete section
     */
    public function destroy($infoBlockId, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($infoBlockId);
        $section = TInfoBlockSection::where('info_block_id', $infoBlockId)->findOrFail($id);
        $sectionName = $section->name;

        // Delete all child sections and elements (cascade)
        $section->delete();

        // Log activity
        TAdminAction::log('deleted', 'info_block_section', $id,
            'Удален раздел "' . $sectionName . '" из инфоблока: ' . $infoBlock->name);

        return response()->json(['message' => 'Раздел удален']);
    }

    /**
     * Get breadcrumbs for section
     */
    public function breadcrumbs($infoBlockId, $id)
    {
        $section = TInfoBlockSection::where('info_block_id', $infoBlockId)->findOrFail($id);
        $breadcrumbs = $section->getBreadcrumbs();

        return response()->json($breadcrumbs);
    }
}
