<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Integrations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\HolartCMS\Models\Integrations\TIntegrationSettings;

class YookassaSettingsController extends Controller
{
    /**
     * Get Yookassa settings
     */
    public function index()
    {
        $settings = TIntegrationSettings::getAll('yookassa');

        return response()->json([
            'shop_id' => $settings['shop_id'] ?? '',
            'secret_key' => $settings['secret_key'] ?? '',
        ]);
    }

    /**
     * Update Yookassa settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'shop_id' => 'nullable|string',
            'secret_key' => 'nullable|string',
        ]);

        TIntegrationSettings::set('yookassa', 'shop_id', $request->shop_id ?? '', 'string');
        TIntegrationSettings::set('yookassa', 'secret_key', $request->secret_key ?? '', 'string');

        return response()->json([
            'success' => true,
            'message' => 'Настройки ЮКassa успешно сохранены'
        ]);
    }
}
