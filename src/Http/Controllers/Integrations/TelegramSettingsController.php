<?php

namespace HolartWeb\HolartCMS\Http\Controllers\Integrations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HolartWeb\HolartCMS\Models\Integrations\TIntegrationSettings;

class TelegramSettingsController extends Controller
{
    /**
     * Get Telegram settings
     */
    public function index()
    {
        $settings = TIntegrationSettings::getAll('telegram');

        return response()->json([
            'bot_token' => $settings['bot_token'] ?? '',
            'chat_ids' => $settings['chat_ids'] ?? [],
        ]);
    }

    /**
     * Update Telegram settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'bot_token' => 'nullable|string',
            'chat_ids' => 'nullable|array',
            'chat_ids.*' => 'string',
        ]);

        TIntegrationSettings::set('telegram', 'bot_token', $request->bot_token ?? '', 'string');
        TIntegrationSettings::set('telegram', 'chat_ids', $request->chat_ids ?? [], 'array');

        return response()->json([
            'success' => true,
            'message' => 'Настройки Telegram успешно сохранены'
        ]);
    }
}
