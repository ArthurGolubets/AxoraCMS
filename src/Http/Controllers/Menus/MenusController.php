<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Menus;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\AxoraCMS\Models\Menus\TMenu;
use HolartWeb\AxoraCMS\Models\Menus\TMenuItem;
use HolartWeb\AxoraCMS\Models\TAdminAction;

class MenusController extends Controller
{
    /**
     * Get all menus
     */
    public function index(Request $request)
    {
        $query = TMenu::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by location
        if ($location = $request->get('location')) {
            $query->where('location', $location);
        }

        // Filter by active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $menus = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json($menus);
    }

    /**
     * Get single menu with items
     */
    public function show($id)
    {
        $menu = TMenu::with(['rootItems' => function ($query) {
            $query->with(['children' => function ($q) {
                $q->orderBy('sort');
            }]);
        }])->findOrFail($id);

        // Format response with nested structure
        $menuData = $menu->toArray();
        $menuData['items'] = $menuData['root_items'] ?? [];
        unset($menuData['root_items']);

        return response()->json([
            'menu' => $menu,
            'items' => $this->buildNestedItems($menu->rootItems)
        ]);
    }

    /**
     * Build nested items structure
     */
    private function buildNestedItems($items)
    {
        return $items->map(function ($item) {
            $itemArray = $item->toArray();
            if ($item->children && $item->children->count() > 0) {
                $itemArray['children'] = $this->buildNestedItems($item->children);
            }
            return $itemArray;
        });
    }

    /**
     * Create new menu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:t_menus,code',
            'location' => 'required|in:header,footer,custom',
            'custom_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = TMenu::generateCode($validated['name']);
        } else {
            $validated['code'] = TMenu::generateCode($validated['code']);
        }

        $menu = TMenu::create($validated);

        // Log activity
        TAdminAction::log('created', 'menu', $menu->id,
            'Создано меню "' . $menu->name . '" (' . $menu->location . ')');

        return response()->json([
            'id' => $menu->id,
            'menu' => $menu,
            'message' => 'Меню создано успешно'
        ], 201);
    }

    /**
     * Update menu
     */
    public function update(Request $request, $id)
    {
        $menu = TMenu::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:t_menus,code,' . $id,
            'location' => 'required|in:header,footer,custom',
            'custom_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Regenerate code if changed
        if (isset($validated['code']) && $validated['code'] !== $menu->code) {
            $validated['code'] = TMenu::generateCode($validated['code'], $id);
        }

        $oldData = $menu->getOriginal();
        $menu->update($validated);

        // Log activity
        TAdminAction::log('updated', 'menu', $menu->id,
            'Обновлено меню "' . $menu->name . '"', [
            'old' => $oldData,
            'new' => $menu->getAttributes()
        ]);

        return response()->json($menu);
    }

    /**
     * Delete menu
     */
    public function destroy($id)
    {
        $menu = TMenu::findOrFail($id);
        $menuName = $menu->name;

        $menu->delete();

        // Log activity
        TAdminAction::log('deleted', 'menu', $id,
            'Удалено меню "' . $menuName . '"');

        return response()->json(['message' => 'Меню удалено']);
    }

    /**
     * Generate unique code
     */
    public function generateCode(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'exclude_id' => 'nullable|integer'
        ]);

        $code = TMenu::generateCode(
            $request->input('name'),
            $request->input('exclude_id')
        );

        return response()->json(['code' => $code]);
    }

    /**
     * Toggle menu active status
     */
    public function toggleActive($id)
    {
        $menu = TMenu::findOrFail($id);
        $menu->is_active = !$menu->is_active;
        $menu->save();

        // Log activity
        $status = $menu->is_active ? 'активировано' : 'деактивировано';
        TAdminAction::log('updated', 'menu', $menu->id,
            'Меню "' . $menu->name . '" ' . $status);

        return response()->json([
            'message' => 'Статус изменен',
            'is_active' => $menu->is_active
        ]);
    }
}
