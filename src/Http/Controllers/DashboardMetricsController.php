<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardMetricsController extends Controller
{
    protected DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Get all metrics
     */
    public function index(): JsonResponse
    {
        try {
            $metrics = $this->metricsService->getAllMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения метрик: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific metric by type
     */
    public function show(string $type): JsonResponse
    {
        try {
            $metric = $this->metricsService->getMetricByType($type);

            if (empty($metric) && $metric !== []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Метрика не найдена или модуль не установлен',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $metric,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения метрики: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users statistics
     */
    public function usersStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getUsersStats(),
        ]);
    }

    /**
     * Get popular pages
     */
    public function popularPages(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getPopularPages($limit),
        ]);
    }

    /**
     * Get recent logs
     */
    public function recentLogs(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getRecentLogs($limit),
        ]);
    }

    /**
     * Get orders statistics
     */
    public function ordersStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getOrdersStats(),
        ]);
    }

    /**
     * Get recent orders
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getRecentOrders($limit),
        ]);
    }

    /**
     * Get promocodes usage
     */
    public function promocodesUsage(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getPromocodesUsage($limit),
        ]);
    }

    /**
     * Get subscriptions count
     */
    public function subscriptionsCount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getSubscriptionsCount(),
        ]);
    }

    /**
     * Get subscriptions chart
     */
    public function subscriptionsChart(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getSubscriptionsChart($days),
        ]);
    }

    /**
     * Get reviews count
     */
    public function reviewsCount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getReviewsCount(),
        ]);
    }

    /**
     * Get requests count
     */
    public function requestsCount(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getRequestsCount(),
        ]);
    }

    /**
     * Get recent requests
     */
    public function recentRequests(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getRecentRequests($limit),
        ]);
    }
}
