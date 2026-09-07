<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Pages;

use HolartWeb\AxoraCMS\Models\Pages\TPage;
use Illuminate\Routing\Controller;

class PageViewerController extends Controller
{
    /**
     * Preview page by slug (for admin only)
     */
    public function preview($slug)
    {
        $page = TPage::where('slug', $slug)->firstOrFail();

        // Load blocks for dynamic pages
        if ($page->type === TPage::TYPE_DYNAMIC) {
            $blocks = $page->blocks()
                ->whereNull('parent_block_id')
                ->with(['blockType', 'childBlocks' => function ($query) {
                    $query->with('blockType')->orderBy('sort');
                    $query->with(['childBlocks' => function ($query) {
                        $query->with('blockType')->orderBy('sort');
                    }]);
                }])
                ->orderBy('sort')
                ->get();

            return view('axora-cms::pages.preview', compact('page', 'blocks'));
        }

        // Static pages
        return view('axora-cms::pages.preview-static', compact('page'));
    }
}
