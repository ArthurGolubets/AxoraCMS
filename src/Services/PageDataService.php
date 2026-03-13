<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Menus\TMenu;
use HolartWeb\AxoraCMS\Models\Menus\TMenuItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use HolartWeb\AxoraCMS\Models\TPanelSettings;

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
     * Get default SEO data from settings
     *
     * @return array
     */
    private function getDefaultSeoData(): array
    {
        try {
            return [
                'meta_title' => TPanelSettings::get('default_meta_title', ''),
                'meta_description' => TPanelSettings::get('default_meta_description', ''),
                'meta_keywords' => TPanelSettings::get('default_meta_keywords', ''),
            ];
        } catch (\Exception $e) {
            return [
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
            ];
        }
    }

    private function getPageMenu(?int $id)
    {
        if (is_null($id)) {
            return [];
        }

        // Получаем все пункты меню для данного меню
        $items = TMenuItem::where('menu_id', $id)
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();

        return $this->buildMenuTree($items);
    }

    private function buildMenuTree($items, $parentId = null)
    {
        $branch = [];

        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $children = $this->buildMenuTree($items, $item->id);

                $menuItem = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->url,
                    'target' => $item->target,
                    'children' => $children
                ];

                $branch[] = $menuItem;
            }
        }

        return $branch;
    }


    private function getPageSettingsData() :?array{
        try {
            return [
                'header_scripts' => TPanelSettings::get('header_code', ''),
                'footer_scripts' => TPanelSettings::get('footer_code', ''),
                'company_name' => TPanelSettings::get('company_name', ''),
                'work_time' => TPanelSettings::get('work_hours', ''),
                'phones' => TPanelSettings::get('phones', []),
                'address' => TPanelSettings::get('addresses', []),
                'social_links' => TPanelSettings::get('social_links', []),
                'menus' => [
                    'header' => $this->getPageMenu( TPanelSettings::get('header_menu_id', null)),
                    'footer' => $this->getPageMenu( TPanelSettings::get('footer_menu_id', null)),
                ]
            ];
        }catch (\Exception $e) {
            return [
                'header_scripts' => '',
                'footer_scripts' => '',
                'company_name' => '',
                'work_time' => '',
                'phones' => [],
                'address' => [],
                'social_links' => [],
                'menus' => [
                    'header' => [],
                    'footer' => [],
                ]
            ];
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

        // If nothing found, return default SEO data
        $defaultSeo = $this->getDefaultSeoData();

        return [
            'type' => 'default',
            'id' => null,
            'title' => $defaultSeo['meta_title'],
            'meta_title' => $defaultSeo['meta_title'],
            'meta_description' => $defaultSeo['meta_description'],
            'meta_keywords' => $defaultSeo['meta_keywords'],
            'content' => null,
            'slug' => null,
            'entity' => null,
        ];
    }

    /**
     * Get page data settings
     *
     * @return array|null
     */
    public function getSettingsData() :?array
    {
        return $this->getPageSettingsData();
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

        $defaultSeo = $this->getDefaultSeoData();

        return [
            'type' => 'page',
            'id' => $page->id,
            'title' => $page->meta_title ?: $page->title,
            'meta_title' => $page->meta_title ?: $page->title ?: $defaultSeo['meta_title'],
            'meta_description' => $page->meta_description ?: $defaultSeo['meta_description'],
            'meta_keywords' => $page->meta_keywords ?: $defaultSeo['meta_keywords'],
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

        $defaultSeo = $this->getDefaultSeoData();

        return [
            'type' => 'catalog',
            'id' => $catalog->id,
            'title' => $catalog->title ?: $catalog->name,
            'meta_title' => $catalog->title ?: $catalog->name ?: $defaultSeo['meta_title'],
            'meta_description' => $catalog->description ?: $defaultSeo['meta_description'],
            'meta_keywords' => $catalog->keywords ?: $defaultSeo['meta_keywords'],
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

        $defaultSeo = $this->getDefaultSeoData();

        return [
            'type' => 'product',
            'id' => $product->id,
            'title' => $product->title ?: $product->name,
            'meta_title' => $product->title ?: $product->name ?: $defaultSeo['meta_title'],
            'meta_description' => $product->description ?: $defaultSeo['meta_description'],
            'meta_keywords' => $product->keywords ?: $defaultSeo['meta_keywords'],
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
        if (class_exists('HolartWeb\AxoraCMS\Models\SEO\TPage')) {
            return 'HolartWeb\AxoraCMS\Models\SEO\TPage';
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
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            return 'HolartWeb\AxoraCMS\Models\Shop\TCatalog';
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
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            return 'HolartWeb\AxoraCMS\Models\Shop\TProduct';
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
