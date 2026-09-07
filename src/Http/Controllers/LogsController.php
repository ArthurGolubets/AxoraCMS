<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\TAdminAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $query = TAdminAction::with('admin')->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->has('action') && $request->action !== '') {
            $query->where('action', $request->action);
        }

        // Filter by entity type
        if ($request->has('entity_type') && $request->entity_type !== '') {
            $query->where('entity_type', $request->entity_type);
        }

        // Filter by admin
        if ($request->has('admin_id') && $request->admin_id !== '') {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->where('created_at', '>=', $request->date_from.' 00:00:00');
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        // Search in description
        if ($request->has('search') && $request->search !== '') {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $logs = $query->paginate(50);

        return response()->json($logs);
    }

    public function actions()
    {
        $actions = TAdminAction::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json($actions);
    }

    public function entityTypes()
    {
        $entityTypes = TAdminAction::select('entity_type')
            ->distinct()
            ->whereNotNull('entity_type')
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return response()->json($entityTypes);
    }
}
