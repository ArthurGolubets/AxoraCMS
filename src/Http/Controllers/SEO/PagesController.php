<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\SEO;

use HolartWeb\AxoraCMS\Console\ScanRoutesCommand;
use HolartWeb\AxoraCMS\Models\SEO\TPage;
use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class PagesController extends Controller
{
    /**
     * Get list of pages with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = TPage::query();

        // Search by title or slug
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        // Filter by status
        if ($request->has('is_active') && ! is_null($request->input('is_active'))) {
            $query->where('is_active', $request->input('is_active') === 'true');
        }

        // Add today's visits count
        $query->withCount([
            'visits as today_views' => function ($q) {
                $q->whereDate('visited_at', today());
            },
        ]);

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $pages = $query->paginate($perPage);

        return response()->json($pages);
    }

    /**
     * Get single page
     */
    public function show($id)
    {
        $page = TPage::findOrFail($id);

        return response()->json($page);
    }

    /**
     * Create new page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'type' => 'required|in:static,dynamic',
            'route_name' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = TPage::generateSlug($data['title']);
        } else {
            $data['slug'] = TPage::generateSlug($data['slug']);
        }

        // Validate slug uniqueness
        if (TPage::where('slug', $data['slug'])->exists()) {
            return response()->json([
                'message' => 'Страница с таким slug уже существует',
            ], 422);
        }

        $page = TPage::create($data);

        // Log activity
        TAdminAction::log('created', 'page', $page->id, 'Создана страница: '.$page->title);

        return response()->json([
            'message' => 'Страница создана успешно',
            'page' => $page,
        ], 201);
    }

    /**
     * Update page
     */
    public function update(Request $request, $id)
    {
        $page = TPage::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'type' => 'sometimes|required|in:static,dynamic',
            'route_name' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle slug update
        if (isset($data['slug']) && $data['slug'] !== $page->slug) {
            $data['slug'] = TPage::generateSlug($data['slug'], $page->id);

            // Validate slug uniqueness
            if (TPage::where('slug', $data['slug'])->where('id', '!=', $page->id)->exists()) {
                return response()->json([
                    'message' => 'Страница с таким slug уже существует',
                ], 422);
            }
        }

        $page->update($data);

        // Log activity
        TAdminAction::log('updated', 'page', $page->id, 'Обновлена страница: '.$page->title);

        return response()->json([
            'message' => 'Страница обновлена успешно',
            'page' => $page,
        ]);
    }

    /**
     * Delete page
     */
    public function destroy($id)
    {
        $page = TPage::findOrFail($id);
        $title = $page->title;

        $page->delete();

        // Log activity
        TAdminAction::log('deleted', 'page', $id, 'Удалена страница: '.$title);

        return response()->json([
            'message' => 'Страница удалена успешно',
        ]);
    }

    /**
     * Toggle page status
     */
    public function toggleStatus($id)
    {
        $page = TPage::findOrFail($id);
        $page->is_active = ! $page->is_active;
        $page->save();

        $status = $page->is_active ? 'активирована' : 'деактивирована';

        // Log activity
        TAdminAction::log('updated', 'page', $page->id, "Страница {$status}: ".$page->title);

        return response()->json([
            'message' => "Страница {$status} успешно",
            'is_active' => $page->is_active,
        ]);
    }

    /**
     * Scan routes and create/update static pages
     */
    public function scanRoutes()
    {
        try {
            // Execute the command using Artisan
            Artisan::call('axoracms:scan-routes');

            // Get the results from the command
            $results = ScanRoutesCommand::getLastResults();

            if ($results) {
                return response()->json([
                    'success' => true,
                    'message' => 'Сканирование маршрутов завершено успешно',
                    'data' => [
                        'created' => $results['created'],
                        'updated' => $results['updated'],
                        'skipped' => $results['skipped'],
                        'routes' => $results['routes'],
                        'total' => count($results['routes']),
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить результаты сканирования',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сканировании маршрутов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get page types for filter
     */
    public function getTypes()
    {
        return response()->json([
            'types' => [
                ['value' => 'all', 'label' => 'Все типы'],
                ['value' => 'static', 'label' => 'Статические'],
                ['value' => 'dynamic', 'label' => 'Динамические'],
            ],
        ]);
    }
}
