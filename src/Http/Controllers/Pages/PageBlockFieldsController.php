<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Pages;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageBlockFieldsController extends Controller
{
    /**
     * Get catalogs for selector
     */
    public function getCatalogs(Request $request)
    {
        // Check if Shop module is installed
        if (!class_exists('HolartWeb\\HolartCMS\\Models\\Shop\\TCatalog')) {
            return response()->json([]);
        }

        $catalogClass = 'HolartWeb\\HolartCMS\\Models\\Shop\\TCatalog';
        $query = $catalogClass::query()->where('is_active', true);

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Get only root catalogs or with parents
        if ($request->boolean('only_root')) {
            $query->whereNull('parent_id');
        }

        $catalogs = $query->orderBy('name')->get(['id', 'name', 'parent_id', 'slug']);

        return response()->json($catalogs);
    }

    /**
     * Get info blocks for selector
     */
    public function getInfoBlocks(Request $request)
    {
        // Check if InfoBlocks module is installed
        if (!class_exists('HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlock')) {
            return response()->json([]);
        }

        $infoBlockClass = 'HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlock';
        $query = $infoBlockClass::query()->where('is_active', true);

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $infoBlocks = $query->orderBy('name')->get(['id', 'name', 'code']);

        return response()->json($infoBlocks);
    }

    /**
     * Get products for selector
     */
    public function getProducts(Request $request)
    {
        // Check if Shop module is installed
        if (!class_exists('HolartWeb\\HolartCMS\\Models\\Shop\\TProduct')) {
            return response()->json([]);
        }

        $productClass = 'HolartWeb\\HolartCMS\\Models\\Shop\\TProduct';
        $query = $productClass::query()->where('is_active', true);

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by catalog
        if ($catalogId = $request->get('catalog_id')) {
            $query->where('catalog_id', $catalogId);
        }

        // Filter by attributes
        if ($request->has('is_new')) {
            $query->where('is_new', $request->boolean('is_new'));
        }

        if ($request->has('is_recommended')) {
            $query->where('is_recommended', $request->boolean('is_recommended'));
        }

        if ($request->has('is_hot')) {
            $query->where('is_hot', $request->boolean('is_hot'));
        }

        // Limit results
        $limit = $request->get('limit', 50);
        $query->limit($limit);

        $products = $query->with('catalog:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'catalog_id', 'price', 'main_image', 'is_new', 'is_recommended', 'is_hot']);

        return response()->json($products);
    }

    /**
     * Get info block elements for selector
     */
    public function getInfoBlockElements(Request $request, $infoBlockId)
    {
        // Check if InfoBlocks module is installed
        if (!class_exists('HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlockElement')) {
            return response()->json([]);
        }

        $elementClass = 'HolartWeb\\HolartCMS\\Models\\InfoBlocks\\TInfoBlockElement';
        $query = $elementClass::query()
            ->where('info_block_id', $infoBlockId)
            ->where('is_active', true);

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Limit results
        $limit = $request->get('limit', 50);
        $query->limit($limit);

        $elements = $query->orderBy('sort')->get(['id', 'name', 'code', 'info_block_id']);

        return response()->json($elements);
    }
}
