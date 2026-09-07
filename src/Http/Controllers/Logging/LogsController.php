<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Logging;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{
    /**
     * Get paginated list of logs with filters
     */
    public function index(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can view logs
        if (! in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $query = TAdminAction::with('admin:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by entity_type
        if ($request->has('entity_type') && $request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        // Filter by admin_id
        if ($request->has('admin_id') && $request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get single log details
     */
    public function show($id)
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can view logs
        if (! in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $log = TAdminAction::with('admin:id,name,email')->findOrFail($id);

        return response()->json($log);
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics(Request $request)
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can view logs
        if (! in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $stats = [
            'total_actions' => TAdminAction::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'by_action' => TAdminAction::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->get(),
            'by_entity_type' => TAdminAction::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('entity_type, count(*) as count')
                ->whereNotNull('entity_type')
                ->groupBy('entity_type')
                ->get(),
            'by_admin' => TAdminAction::with('admin:id,name')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('admin_id, count(*) as count')
                ->groupBy('admin_id')
                ->get(),
            'recent_actions' => TAdminAction::with('admin:id,name,email')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get available filter options
     */
    public function filters()
    {
        $user = Auth::guard('admin')->user();

        // Only super_admin and administrator can view logs
        if (! in_array($user->role->value, ['super_admin', 'administrator'])) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $actions = TAdminAction::distinct()->pluck('action');
        $entityTypes = TAdminAction::distinct()->whereNotNull('entity_type')->pluck('entity_type');
        $admins = TAdminAction::with('admin:id,name')
            ->distinct()
            ->get()
            ->pluck('admin')
            ->unique('id')
            ->values();

        return response()->json([
            'actions' => $actions,
            'entity_types' => $entityTypes,
            'admins' => $admins,
        ]);
    }
}
