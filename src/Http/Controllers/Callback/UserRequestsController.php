<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Callback;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Callback\TUserRequests;

class UserRequestsController extends Controller
{
    /**
     * Display a listing of user requests
     */
    public function index(Request $request)
    {
        $query = TUserRequests::query();

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('comment', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $userRequests = $query->paginate($perPage);

        return response()->json($userRequests);
    }

    /**
     * Display the specified user request
     */
    public function show($id)
    {
        $userRequest = TUserRequests::findOrFail($id);
        return response()->json($userRequest);
    }

    /**
     * Store a newly created user request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'comment' => 'required|string',
            'user_id' => 'nullable|integer|exists:t_users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userRequest = TUserRequests::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Обращение успешно добавлено',
            'data' => $userRequest
        ], 201);
    }

    /**
     * Update the specified user request
     */
    public function update(Request $request, $id)
    {
        $userRequest = TUserRequests::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'comment' => 'required|string',
            'user_id' => 'nullable|integer|exists:t_users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userRequest->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Обращение успешно обновлено',
            'data' => $userRequest
        ]);
    }

    /**
     * Remove the specified user request
     */
    public function destroy($id)
    {
        $userRequest = TUserRequests::findOrFail($id);
        $userRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Обращение успешно удалено'
        ]);
    }

    /**
     * Bulk delete user requests
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:t_user_requests,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        TUserRequests::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Обращения успешно удалены'
        ]);
    }
}
