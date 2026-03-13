<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Menus;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\AxoraCMS\Models\Menus\TMenuItem;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class MenuItemsController extends Controller
{
    /**
     * Get all items for a menu (flat array, frontend builds tree)
     */
    public function index($menuId)
    {
        $items = TMenuItem::where('menu_id', $menuId)
            ->orderBy('sort')
            ->get();

        return response()->json($items);
    }

    /**
     * Create new menu item
     */
    public function store(Request $request, $menuId = null)
    {
        $validated = $request->validate([
            'menu_id' => 'sometimes|required|exists:t_menus,id',
            'parent_id' => 'nullable|exists:t_menu_items,id',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string',
            'route' => 'nullable|string',
            'target' => 'nullable|in:_self,_blank',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_attributes' => 'nullable|array',
        ]);

        // Use menuId from URL if provided, otherwise use from request body
        if ($menuId) {
            $validated['menu_id'] = $menuId;
        }

        $item = TMenuItem::create($validated);

        // Log activity
        TAdminAction::log('created', 'menu_item', $item->id,
            'Создан пункт меню "' . $item->title . '"');

        return response()->json($item, 201);
    }

    /**
     * Update menu item
     */
    public function update(Request $request, $id)
    {
        $item = TMenuItem::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:t_menu_items,id',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string',
            'route' => 'nullable|string',
            'target' => 'nullable|in:_self,_blank',
            'sort' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_attributes' => 'nullable|array',
        ]);

        $oldData = $item->getOriginal();
        $item->update($validated);

        // Log activity
        TAdminAction::log('updated', 'menu_item', $item->id,
            'Обновлен пункт меню "' . $item->title . '"', [
            'old' => $oldData,
            'new' => $item->getAttributes()
        ]);

        return response()->json($item);
    }

    /**
     * Delete menu item
     */
    public function destroy($id)
    {
        $item = TMenuItem::findOrFail($id);
        $itemTitle = $item->title;

        $item->delete();

        // Log activity
        TAdminAction::log('deleted', 'menu_item', $id,
            'Удален пункт меню "' . $itemTitle . '"');

        return response()->json(['message' => 'Пункт меню удален']);
    }

    /**
     * Reorder menu items
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:t_menu_items,id',
            'items.*.sort' => 'required|integer',
            'items.*.parent_id' => 'nullable|exists:t_menu_items,id',
        ]);

        foreach ($validated['items'] as $itemData) {
            TMenuItem::where('id', $itemData['id'])->update([
                'sort' => $itemData['sort'],
                'parent_id' => $itemData['parent_id'] ?? null,
            ]);
        }

        // Log activity
        TAdminAction::log('reordered', 'menu_items', null,
            'Изменен порядок пунктов меню');

        return response()->json(['message' => 'Порядок обновлен']);
    }

    /**
     * Toggle menu item active status
     */
    public function toggleActive($id)
    {
        $item = TMenuItem::findOrFail($id);
        $item->is_active = !$item->is_active;
        $item->save();

        // Log activity
        $status = $item->is_active ? 'активирован' : 'деактивирован';
        TAdminAction::log('updated', 'menu_item', $item->id,
            'Пункт меню "' . $item->title . '" ' . $status);

        return response()->json([
            'message' => 'Статус изменен',
            'is_active' => $item->is_active
        ]);
    }
}
