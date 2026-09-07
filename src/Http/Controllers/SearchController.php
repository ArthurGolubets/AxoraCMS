<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Models\TAdministrator;
use HolartWeb\AxoraCMS\Models\TPanelSettings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Global search across all entities.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $user = Auth::guard('admin')->user();
        $results = [];

        // Search administrators (only for super_admin and administrator)
        if (in_array($user->role, ['super_admin', 'administrator'])) {
            $administrators = TAdministrator::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($administrators as $admin) {
                $results[] = [
                    'id' => $admin->id,
                    'entity' => 'administrators',
                    'name' => $admin->name,
                    'subtitle' => $admin->email,
                    'url' => '/administrators',
                ];
            }
        }

        // Search catalogs if module exists
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TCatalog')) {
            $catalogClass = 'HolartWeb\AxoraCMS\Models\Shop\TCatalog';
            $catalogs = $catalogClass::where('name', 'like', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($catalogs as $catalog) {
                $results[] = [
                    'id' => $catalog->id,
                    'entity' => 'catalogs',
                    'name' => $catalog->name,
                    'subtitle' => 'Категория',
                    'url' => "/catalog?search={$catalog->name}",
                ];
            }
        }

        // Search products if module exists
        if (class_exists('HolartWeb\AxoraCMS\Models\Shop\TProduct')) {
            $productClass = 'HolartWeb\AxoraCMS\Models\Shop\TProduct';
            $products = $productClass::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->with('catalog')
                ->limit(10)
                ->get();

            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->id,
                    'entity' => 'products',
                    'name' => $product->name,
                    'subtitle' => $product->catalog ? $product->catalog->name : "SKU: {$product->sku}",
                    'url' => "/products/{$product->id}/edit",
                ];
            }
        }

        // Search settings (only for super_admin and administrator)
        if (in_array($user->role, ['super_admin', 'administrator'])) {
            $settings = TPanelSettings::where('key', 'like', "%{$query}%")
                ->limit(5)
                ->get();

            foreach ($settings as $setting) {
                $results[] = [
                    'id' => $setting->id,
                    'entity' => 'settings',
                    'name' => $setting->key,
                    'subtitle' => 'Настройка',
                    'url' => '/settings',
                ];
            }
        }

        return response()->json($results);
    }
}
