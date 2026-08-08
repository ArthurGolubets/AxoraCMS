<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\InfoBlocks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use HolartWeb\AxoraCMS\Models\InfoBlocks\TInfoBlock;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class InfoBlocksController extends Controller
{
    /**
     * Get all info blocks
     */
    public function index(Request $request)
    {
        $query = TInfoBlock::query();

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

        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $infoBlocks = $query->paginate($perPage);

        // Add counts to each info block
        $infoBlocks->getCollection()->transform(function($infoBlock) {
            $infoBlock->elements_count = $infoBlock->elements()->count();
            $infoBlock->fields_count = $infoBlock->fields()->count();
            $infoBlock->sections_count = $infoBlock->sections()->count();
            return $infoBlock;
        });

        return response()->json($infoBlocks);
    }

    /**
     * Get single info block
     */
    public function show($id)
    {
        $infoBlock = TInfoBlock::with(['fields' => function($query) {
            $query->orderBy('sort');
        }])->findOrFail($id);

        $infoBlock->elements_count = $infoBlock->elements()->count();
        $infoBlock->fields_count = $infoBlock->fields()->count();
        $infoBlock->sections_count = $infoBlock->sections()->count();

        return response()->json($infoBlock);
    }

    /**
     * Create new info block
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:list,catalog',
            'code' => 'nullable|string|unique:t_info_blocks,code|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = TInfoBlock::generateCode($validated['name']);
        }

        // Generate table name
        $validated['table_name'] = TInfoBlock::generateTableName($validated['code']);

        $infoBlock = TInfoBlock::create($validated);

        // Log activity
        TAdminAction::log('created', 'info_block', $infoBlock->id, 'Создан инфоблок: ' . $infoBlock->name);

        return response()->json($infoBlock, 201);
    }

    /**
     * Update info block
     */
    public function update(Request $request, $id)
    {
        $infoBlock = TInfoBlock::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:list,catalog',
            'code' => 'nullable|string|unique:t_info_blocks,code,' . $id . '|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $oldData = $infoBlock->getOriginal();
        $infoBlock->update($validated);

        // Log activity
        TAdminAction::log('updated', 'info_block', $infoBlock->id, 'Обновлен инфоблок: ' . $infoBlock->name, [
            'old' => $oldData,
            'new' => $infoBlock->getAttributes()
        ]);

        return response()->json($infoBlock);
    }

    /**
     * Delete info block
     */
    public function destroy($id)
    {
        $infoBlock = TInfoBlock::findOrFail($id);
        $infoBlockName = $infoBlock->name;

        // Delete all fields, sections and elements
        $infoBlock->fields()->delete();
        $infoBlock->sections()->delete();
        $infoBlock->elements()->delete();
        $infoBlock->delete();

        // Log activity
        TAdminAction::log('deleted', 'info_block', $id, 'Удален инфоблок: ' . $infoBlockName);

        return response()->json(['message' => 'Инфоблок удален']);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite($id)
    {
        $infoBlock = TInfoBlock::findOrFail($id);
        $infoBlock->is_favorite = !$infoBlock->is_favorite;
        $infoBlock->save();

        // Log activity
        TAdminAction::log(
            $infoBlock->is_favorite ? 'favorite_added' : 'favorite_removed',
            'info_block',
            $infoBlock->id,
            ($infoBlock->is_favorite ? 'Добавлен в избранное: ' : 'Удален из избранного: ') . $infoBlock->name
        );

        return response()->json([
            'is_favorite' => $infoBlock->is_favorite,
            'message' => $infoBlock->is_favorite ? 'Добавлено в избранное' : 'Удалено из избранного'
        ]);
    }

    /**
     * Get favorite info blocks for sidebar
     */
    public function favorites()
    {
        $favorites = TInfoBlock::where('is_favorite', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json($favorites);
    }
}
