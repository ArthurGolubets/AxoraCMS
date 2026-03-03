<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\HolartCMS\Models\Pages\TPage;
use HolartWeb\HolartCMS\Models\TAdminAction;

class PagesController extends Controller
{
    /**
     * Get all pages
     */
    public function index(Request $request)
    {
        $query = TPage::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filter by active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $pages = $query->orderBy('sort')->orderBy('id', 'desc')->paginate(20);

        return response()->json($pages);
    }

    /**
     * Get single page
     */
    public function show($id)
    {
        $page = TPage::with('blocks.blockType')->findOrFail($id);
        return response()->json($page);
    }

    /**
     * Create new page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:t_pages,slug',
            'type' => 'required|in:static,dynamic',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort' => 'nullable|integer',
            'header_template' => 'nullable|string',
            'footer_template' => 'nullable|string',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = TPage::generateSlug($validated['title']);
        } else {
            $validated['slug'] = TPage::generateSlug($validated['slug']);
        }

        // Dynamic pages are unpublished by default
        if ($validated['type'] === TPage::TYPE_DYNAMIC && !isset($validated['is_active'])) {
            $validated['is_active'] = false;
        }

        $page = TPage::create($validated);

        // Log activity
        TAdminAction::log('created', 'page', $page->id,
            'Создана страница "' . $page->title . '" (' . $page->type . ')');

        return response()->json($page, 201);
    }

    /**
     * Update page
     */
    public function update(Request $request, $id)
    {
        $page = TPage::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:t_pages,slug,' . $id,
            'type' => 'required|in:static,dynamic',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort' => 'nullable|integer',
            'header_template' => 'nullable|string',
            'footer_template' => 'nullable|string',
        ]);

        // Regenerate slug if changed
        if (isset($validated['slug']) && $validated['slug'] !== $page->slug) {
            $validated['slug'] = TPage::generateSlug($validated['slug'], $id);
        }

        $oldData = $page->getOriginal();
        $page->update($validated);

        // Log activity
        TAdminAction::log('updated', 'page', $page->id,
            'Обновлена страница "' . $page->title . '"', [
            'old' => $oldData,
            'new' => $page->getAttributes()
        ]);

        return response()->json($page);
    }

    /**
     * Delete page
     */
    public function destroy($id)
    {
        $page = TPage::findOrFail($id);
        $pageName = $page->title;

        $page->delete();

        // Log activity
        TAdminAction::log('deleted', 'page', $id,
            'Удалена страница "' . $pageName . '"');

        return response()->json(['message' => 'Страница удалена']);
    }

    /**
     * Duplicate page
     */
    public function duplicate($id)
    {
        $page = TPage::with('blocks')->findOrFail($id);
        $newPage = $page->duplicate();

        // Log activity
        TAdminAction::log('duplicated', 'page', $newPage->id,
            'Дублирована страница "' . $page->title . '" → "' . $newPage->title . '"');

        return response()->json($newPage, 201);
    }

    /**
     * Generate unique slug
     */
    public function generateSlug(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'exclude_id' => 'nullable|integer'
        ]);

        $slug = TPage::generateSlug(
            $request->input('title'),
            $request->input('exclude_id')
        );

        return response()->json(['slug' => $slug]);
    }

    /**
     * Toggle page publication status
     */
    public function togglePublish($id)
    {
        $page = TPage::findOrFail($id);
        $page->is_active = !$page->is_active;
        $page->save();

        // Log activity
        $status = $page->is_active ? 'опубликована' : 'снята с публикации';
        TAdminAction::log('updated', 'page', $page->id,
            'Страница "' . $page->title . '" ' . $status);

        return response()->json([
            'message' => 'Статус публикации изменен',
            'is_active' => $page->is_active
        ]);
    }
}
