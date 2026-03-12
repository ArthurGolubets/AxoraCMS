<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Callback;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Callback\TComments;

class CommentsController extends Controller
{
    /**
     * Display a listing of comments
     */
    public function index(Request $request)
    {
        $query = TComments::query();

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('comment', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by moderation status
        if ($request->has('is_moderated') && $request->is_moderated !== '') {
            $query->where('is_moderated', $request->is_moderated === 'true' || $request->is_moderated === '1');
        }

        // Filter by rating
        if ($request->has('rating') && $request->rating !== '') {
            $query->where('rating', $request->rating);
        }

        // Filter by product
        if ($request->has('product_id') && $request->product_id !== '') {
            $query->where('product_id', $request->product_id);
        }

        // Load product relationship if exists
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            $query->with('product:id,name');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $comments = $query->paginate($perPage);

        return response()->json($comments);
    }

    /**
     * Display the specified comment
     */
    public function show($id)
    {
        $comment = TComments::with('product')->findOrFail($id);
        return response()->json($comment);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:0|max:5',
            'product_id' => 'nullable|integer|exists:t_products,id',
            'is_moderated' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = TComments::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно добавлен',
            'data' => $comment
        ], 201);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, $id)
    {
        $comment = TComments::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'comment' => 'required|string',
            'rating' => 'required|integer|min:0|max:5',
            'product_id' => 'nullable|integer|exists:t_products,id',
            'is_moderated' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно обновлен',
            'data' => $comment
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy($id)
    {
        $comment = TComments::findOrFail($id);
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно удален'
        ]);
    }

    /**
     * Bulk delete comments
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:t_comments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        TComments::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Комментарии успешно удалены'
        ]);
    }

    /**
     * Toggle moderation status
     */
    public function toggleModeration($id)
    {
        $comment = TComments::findOrFail($id);
        $comment->is_moderated = !$comment->is_moderated;
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Статус модерации изменен',
            'data' => $comment
        ]);
    }
}
