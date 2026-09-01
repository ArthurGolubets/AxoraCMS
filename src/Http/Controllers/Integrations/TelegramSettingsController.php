<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Integrations;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
            'send_mode' => $settings['send_mode'] ?? 'default',
            'external_url' => $settings['external_url'] ?? '',
            'external_token' => $settings['external_token'] ?? '',
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
            'send_mode' => 'nullable|in:default,external',
            'external_url' => 'nullable|string|max:2048',
            'external_token' => 'nullable|string|max:1024',
        ]);

        TIntegrationSettings::set('telegram', 'bot_token', $request->bot_token ?? '', 'string');
        TIntegrationSettings::set('telegram', 'chat_ids', $request->chat_ids ?? [], 'array');
        TIntegrationSettings::set('telegram', 'send_mode', $request->input('send_mode', 'default'), 'string');
        TIntegrationSettings::set('telegram', 'external_url', $request->external_url ?? '', 'string');
        TIntegrationSettings::set('telegram', 'external_token', $request->external_token ?? '', 'string');

        return response()->json([
            'success' => true,
            'message' => 'Настройки Telegram успешно сохранены',
        ]);
    }
}
