<?php

namespace HolartWeb\HolartCMS\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PageDataService
{
    /**
     * Check if database is configured and accessible
     */
    private function isDatabaseAvailable(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * Get page data by current route
     *
     * @return array|null
     */
    public function getPageData(): ?array
    {
        $currentRoute = Route::current();

        if (!$currentRoute) {
            return null;
        }

        $routeName = $currentRoute->getName();
        $uri = $currentRoute->uri();
        $currentUrl = request()->path();

        // Try to find page by route name or URL
        $pageData = $this->findPageByRoute($routeName, $currentUrl);

        if ($pageData) {
            return $pageData;
        }

        // Try to find catalog by slug
        $catalogData = $this->findCatalogByUrl($currentUrl);

        if ($catalogData) {
            return $catalogData;
        }

        // Try to find product by slug
        $productData = $this->findProductByUrl($currentUrl);

        if ($productData) {
            return $productData;
        }

        return null;
    }

    /**
     * Check if current route has inactive entity
     *
     * @return bool
     */
    public function hasInactiveEntity(): bool
    {
        $currentRoute = Route::current();

        if (!$currentRoute) {
            return false;
        }

        $routeName = $currentRoute->getName();
        $currentUrl = request()->path();

        // Check for inactive page
        if ($this->hasInactivePage($routeName, $currentUrl)) {
            return true;
        }

        // Check for inactive catalog
        if ($this->hasInactiveCatalog($currentUrl)) {
            return true;
        }

        // Check for inactive product
        if ($this->hasInactiveProduct($currentUrl)) {
            return true;
        }

        return false;
    }

    /**
     * Find page by route name or URL
     *
     * @param string|null $routeName
     * @param string $url
     * @return array|null
     */
    private function findPageByRoute(?string $routeName, string $url): ?array
    {
        if (!$this->isDatabaseAvailable()) {
            return null;
        }

        try {
            if (!Schema::hasTable('t_pages')) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $pageModel = $this->getPageModel();

        if (!$pageModel) {
            return null;
        }

        $page = null;

        // Try to find by route name first
        if ($routeName) {
            $page = $pageModel::where('route_name', $routeName)
                ->where('is_active', true)
                ->first();
        }

        // If not found by route name, try by slug
        if (!$page) {
            $slug = trim($url, '/') ?: 'home';
            $page = $pageModel::where('slug', $slug)
                ->where('is_active', true)
                ->first();
        }

        if (!$page) {
            return null;
        }

        return [
            'type' => 'page',
            'id' => $page->id,
            'title' => $page->meta_title ?: $page->title,
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'content' => $page->content,
            'slug' => $page->slug,
            'entity' => $page,
        ];
    }

    /**
     * Find catalog by URL
     *
     * @param string $url
     * @return array|null
     */
    private function findCatalogByUrl(string $url): ?array
    {
        if (!$this->isDatabaseAvailable()) {
            return null;
        }

        try {
            if (!Schema::hasTable('t_catalogs')) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $catalogModel = $this->getCatalogModel();

        if (!$catalogModel) {
            return null;
        }

        // Extract catalog slug from URL (e.g., /catalog/technika -> technika)
        if (!preg_match('#^catalog/([^/]+)$#', $url, $matches)) {
            return null;
        }

        $slug = $matches[1];

        $catalog = $catalogModel::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$catalog) {
            return null;
        }

        return [
            'type' => 'catalog',
            'id' => $catalog->id,
            'title' => $catalog->title ?: $catalog->name,
            'meta_title' => $catalog->title ?: $catalog->name,
            'meta_description' => $catalog->description,
            'meta_keywords' => $catalog->keywords,
            'content' => $catalog->content,
            'slug' => $catalog->slug,
            'entity' => $catalog,
        ];
    }

    /**
     * Find product by URL
     *
     * @param string $url
     * @return array|null
     */
    private function findProductByUrl(string $url): ?array
    {
        if (!$this->isDatabaseAvailable()) {
            return null;
        }

        try {
            if (!Schema::hasTable('t_products')) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $productModel = $this->getProductModel();

        if (!$productModel) {
            return null;
        }

        // Extract product slug from URL (e.g., /product/macbook-air-2pro -> macbook-air-2pro)
        if (!preg_match('#^product/([^/]+)$#', $url, $matches)) {
            return null;
        }

        $slug = $matches[1];

        $product = $productModel::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return null;
        }

        return [
            'type' => 'product',
            'id' => $product->id,
            'title' => $product->title ?: $product->name,
            'meta_title' => $product->title ?: $product->name,
            'meta_description' => $product->description,
            'meta_keywords' => $product->keywords,
            'content' => $product->content,
            'slug' => $product->slug,
            'entity' => $product,
        ];
    }

    /**
     * Get Page model class
     *
     * @return string|null
     */
    private function getPageModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\SEO\TPage')) {
            return 'HolartWeb\HolartCMS\Models\SEO\TPage';
        }

        return null;
    }

    /**
     * Get Catalog model class
     *
     * @return string|null
     */
    private function getCatalogModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TCatalog')) {
            return 'HolartWeb\HolartCMS\Models\Shop\TCatalog';
        }

        return null;
    }

    /**
     * Get Product model class
     *
     * @return string|null
     */
    private function getProductModel(): ?string
    {
        // Use package model
        if (class_exists('HolartWeb\HolartCMS\Models\Shop\TProduct')) {
            return 'HolartWeb\HolartCMS\Models\Shop\TProduct';
        }

        return null;
    }

    /**
     * Check if page exists but is inactive
     *
     * @param string|null $routeName
     * @param string $url
     * @return bool
     */
    private function hasInactivePage(?string $routeName, string $url): bool
    {
        if (!$this->isDatabaseAvailable()) {
            return false;
        }

        try {
            if (!Schema::hasTable('t_pages')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $pageModel = $this->getPageModel();

        if (!$pageModel) {
            return false;
        }

        $page = null;

        // Try to find by route name first
        if ($routeName) {
            $page = $pageModel::where('route_name', $routeName)->first();
        }

        // If not found by route name, try by slug
        if (!$page) {
            $slug = trim($url, '/') ?: 'home';
            $page = $pageModel::where('slug', $slug)->first();
        }

        // Return true if page exists but is inactive
        return $page && !$page->is_active;
    }

    /**
     * Check if catalog exists but is inactive
     *
     * @param string $url
     * @return bool
     */
    private function hasInactiveCatalog(string $url): bool
    {
        if (!$this->isDatabaseAvailable()) {
            return false;
        }

        try {
            if (!Schema::hasTable('t_catalogs')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $catalogModel = $this->getCatalogModel();

        if (!$catalogModel) {
            return false;
        }

        // Extract catalog slug from URL
        if (!preg_match('#^catalog/([^/]+)$#', $url, $matches)) {
            return false;
        }

        $slug = $matches[1];

        $catalog = $catalogModel::where('slug', $slug)->first();

        // Return true if catalog exists but is inactive
        return $catalog && !$catalog->is_active;
    }

    /**
     * Check if product exists but is inactive
     *
     * @param string $url
     * @return bool
     */
    private function hasInactiveProduct(string $url): bool
    {
        if (!$this->isDatabaseAvailable()) {
            return false;
        }

        try {
            if (!Schema::hasTable('t_products')) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $productModel = $this->getProductModel();

        if (!$productModel) {
            return false;
        }

        // Extract product slug from URL
        if (!preg_match('#^product/([^/]+)$#', $url, $matches)) {
            return false;
        }

        $slug = $matches[1];

        $product = $productModel::where('slug', $slug)->first();

        // Return true if product exists but is inactive
        return $product && !$product->is_active;
    }
}
