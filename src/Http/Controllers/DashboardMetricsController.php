<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DashboardMetricsController extends Controller
{
    /**
     * Metrics of the widgets listed in ?types=a,b,c
     */
    public function index(Request $request, DashboardMetricsService $metrics): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $metrics->getMetrics(array_filter(explode(',', (string) $request->query('types')))),
            ]);
        } catch (\Exception $e) {
            Log::warning('Dashboard metrics failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ошибка получения метрик'], 500);
        }
    }
}
