<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard (Vue.js SPA).
     */
    public function index()
    {
        return view('axora-cms::dashboard');
    }

    /**
     * Get current authenticated admin user.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);
    }
}
