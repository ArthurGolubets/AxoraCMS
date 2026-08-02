<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use HolartWeb\AxoraCMS\Models\Integration\TCommerceMLSetting;
use HolartWeb\AxoraCMS\Models\Shop\TProduct;
use HolartWeb\AxoraCMS\Models\Shop\TCatalog;

class CommerceMLController
{
    /**
     * Get CommerceML settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = TCommerceMLSetting::getSettings();

        // Get statistics
        $productsCount = TProduct::whereNotNull('1c_id')->count();
        $catalogsCount = TCatalog::whereNotNull('1c_id')->count();

        return response()->json([
            'settings' => [
                'login' => $settings->login,
                'password' => $settings->password,
                'import_type' => $settings->import_type,
                'is_enabled' => $settings->is_enabled,
            ],
            'statistics' => [
                'products_count' => $productsCount,
                'catalogs_count' => $catalogsCount,
                'last_sync' => $settings->updated_at?->format('Y-m-d H:i:s'),
            ],
            'exchange_url' => url('/api/1c/exchange'),
        ]);
    }

    /**
     * Update CommerceML settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'import_type' => 'required|in:separate,monolith',
            'is_enabled' => 'boolean',
        ]);

        $settings = TCommerceMLSetting::getSettings();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Настройки успешно сохранены',
            'settings' => [
                'login' => $settings->login,
                'password' => $settings->password,
                'import_type' => $settings->import_type,
                'is_enabled' => $settings->is_enabled,
            ],
        ]);
    }

    /**
     * Test connection
     */
    public function testConnection(): JsonResponse
    {
        $settings = TCommerceMLSetting::getSettings();

        if (!$settings->is_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Интеграция отключена',
            ], 400);
        }

        if (!$settings->login || !$settings->password) {
            return response()->json([
                'success' => false,
                'message' => 'Логин и пароль не настроены',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки корректны. URL для обмена: ' . url('/api/1c/exchange'),
        ]);
    }
}
