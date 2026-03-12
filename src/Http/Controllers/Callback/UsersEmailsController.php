<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Callback;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use HolartWeb\HolartCMS\Models\Callback\TUsersEmails;

class UsersEmailsController extends Controller
{
    /**
     * Display a listing of users emails
     */
    public function index(Request $request)
    {
        $query = TUsersEmails::query();

        // Search
        if ($request->has('search') && $request->search !== '') {
            $query->where('email', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $emails = $query->paginate($perPage);

        return response()->json($emails);
    }

    /**
     * Display the specified users email
     */
    public function show($id)
    {
        $email = TUsersEmails::findOrFail($id);
        return response()->json($email);
    }

    /**
     * Store a newly created users email
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:t_users_emails,email',
            'status' => 'required|in:active,unsubscribed,bounced'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = TUsersEmails::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Email успешно добавлен',
            'data' => $email
        ], 201);
    }

    /**
     * Update the specified users email
     */
    public function update(Request $request, $id)
    {
        $email = TUsersEmails::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:t_users_emails,email,' . $id,
            'status' => 'required|in:active,unsubscribed,bounced'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Email успешно обновлен',
            'data' => $email
        ]);
    }

    /**
     * Remove the specified users email
     */
    public function destroy($id)
    {
        $email = TUsersEmails::findOrFail($id);
        $email->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email успешно удален'
        ]);
    }

    /**
     * Bulk delete users emails
     */
    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:t_users_emails,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        TUsersEmails::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emails успешно удалены'
        ]);
    }
}
