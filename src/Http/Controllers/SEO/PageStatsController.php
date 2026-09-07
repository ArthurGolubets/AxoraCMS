<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\SEO;

use HolartWeb\AxoraCMS\Services\PageVisitService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageStatsController extends Controller
{
    protected PageVisitService $pageVisitService;

    public function __construct(PageVisitService $pageVisitService)
    {
        $this->pageVisitService = $pageVisitService;
    }

    /**
     * Get general statistics
     */
    public function index(Request $request)
    {
        $period = $request->input('period', '30days');

        return response()->json([
            'total_visits' => $this->pageVisitService->getTotalVisits($period),
            'total_visits_all_time' => $this->pageVisitService->getTotalVisits('all'),
            'popular_pages' => $this->pageVisitService->getPopularPages(10, $period),
            'unlinked_visits' => $this->pageVisitService->getUnlinkedVisits(10),
        ]);
    }

    /**
     * Get visits chart data
     */
    public function chart(Request $request)
    {
        $period = $request->input('period', '7days');
        $chart = $this->pageVisitService->getVisitsChart($period);

        return response()->json($chart);
    }

    /**
     * Get statistics for specific page
     */
    public function pageStats($pageId)
    {
        $stats = $this->pageVisitService->getPageStatistics($pageId);

        return response()->json($stats);
    }

    /**
     * Get popular pages
     */
    public function popular(Request $request)
    {
        $limit = $request->input('limit', 10);
        $period = $request->input('period', '30days');

        $pages = $this->pageVisitService->getPopularPages($limit, $period);

        return response()->json($pages);
    }

    /**
     * Get unlinked visits (pages without records in t_pages)
     */
    public function unlinked(Request $request)
    {
        $limit = $request->input('limit', 20);
        $visits = $this->pageVisitService->getUnlinkedVisits($limit);

        return response()->json($visits);
    }
}
