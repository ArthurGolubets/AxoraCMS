<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Pages;

use HolartWeb\AxoraCMS\Models\Pages\TPage;
use HolartWeb\AxoraCMS\Models\Pages\TPageBlock;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageBlocksController extends Controller
{
    /**
     * Get all blocks for a page
     */
    public function index(Request $request, $pageId)
    {
        $page = TPage::findOrFail($pageId);

        // Only get top-level blocks (not nested inside other blocks)
        $query = $page->blocks()
            ->whereNull('parent_block_id')
            ->with('blockType')
            ->orderBy('sort');

        // Support for nested blocks with recursive loading
        if ($request->query('with_children') == '1') {
            $query->with(['childBlocks' => function ($query) {
                $query->with('blockType')->orderBy('sort');
                // Recursively load nested children
                $query->with(['childBlocks' => function ($query) {
                    $query->with('blockType')->orderBy('sort');
                    // Support up to 3 levels deep
                    $query->with(['childBlocks' => function ($query) {
                        $query->with('blockType')->orderBy('sort');
                    }]);
                }]);
            }]);
        }

        $blocks = $query->get();

        return response()->json($blocks);
    }

    /**
     * Get single block
     */
    public function show(Request $request, $pageId, $id)
    {
        $query = TPageBlock::where('page_id', $pageId)
            ->with('blockType');

        // Support for nested blocks with recursive loading
        if ($request->query('with_children') == '1') {
            $query->with(['childBlocks' => function ($query) {
                $query->with('blockType')->orderBy('sort');
                // Recursively load nested children
                $query->with(['childBlocks' => function ($query) {
                    $query->with('blockType')->orderBy('sort');
                    // Support up to 3 levels deep
                    $query->with(['childBlocks' => function ($query) {
                        $query->with('blockType')->orderBy('sort');
                    }]);
                }]);
            }]);
        }

        $block = $query->findOrFail($id);

        return response()->json($block);
    }

    /**
     * Create new block
     */
    public function store(Request $request, $pageId)
    {
        $page = TPage::findOrFail($pageId);

        if ($page->type !== TPage::TYPE_DYNAMIC) {
            return response()->json([
                'message' => 'Блоки можно добавлять только к динамическим страницам',
            ], 422);
        }

        $validated = $request->validate([
            'block_type_id' => 'required|exists:t_page_block_types,id',
            'parent_block_id' => 'nullable|exists:t_page_blocks,id',
            'container_column' => 'nullable|string',
            'settings' => 'nullable|array',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['page_id'] = $pageId;

        $block = TPageBlock::create($validated);

        // Log activity
        TAdminAction::log('created', 'page_block', $block->id,
            'Добавлен блок на страницу "'.$page->title.'"');

        return response()->json($block->load('blockType'), 201);
    }

    /**
     * Update block
     */
    public function update(Request $request, $pageId, $id)
    {
        $page = TPage::findOrFail($pageId);
        $block = TPageBlock::where('page_id', $pageId)->findOrFail($id);

        $validated = $request->validate([
            'block_type_id' => 'required|exists:t_page_block_types,id',
            'parent_block_id' => 'nullable|exists:t_page_blocks,id',
            'container_column' => 'nullable|string',
            'settings' => 'nullable|array',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $oldData = $block->getOriginal();
        $block->update($validated);

        // Log activity
        TAdminAction::log('updated', 'page_block', $block->id,
            'Обновлен блок на странице "'.$page->title.'"', [
                'old' => $oldData,
                'new' => $block->getAttributes(),
            ]);

        return response()->json($block->load('blockType'));
    }

    /**
     * Delete block
     */
    public function destroy($pageId, $id)
    {
        $page = TPage::findOrFail($pageId);
        $block = TPageBlock::where('page_id', $pageId)->findOrFail($id);

        $block->delete();

        // Log activity
        TAdminAction::log('deleted', 'page_block', $id,
            'Удален блок со страницы "'.$page->title.'"');

        return response()->json(['message' => 'Блок удален']);
    }

    /**
     * Reorder blocks
     */
    public function reorder(Request $request, $pageId)
    {
        $page = TPage::findOrFail($pageId);

        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|exists:t_page_blocks,id',
            'blocks.*.sort' => 'required|integer',
        ]);

        foreach ($request->input('blocks') as $blockData) {
            TPageBlock::where('id', $blockData['id'])
                ->where('page_id', $pageId)
                ->update(['sort' => $blockData['sort']]);
        }

        // Log activity
        TAdminAction::log('reordered', 'page_blocks', $pageId,
            'Изменен порядок блоков на странице "'.$page->title.'"');

        return response()->json(['message' => 'Порядок блоков обновлен']);
    }
}
