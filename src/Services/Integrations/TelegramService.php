<?php

namespace HolartWeb\AxoraCMS\Services\Integrations;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    /**
     * Send message to Telegram
     *
     * @param  string  $message  Message text
     * @param  array|null  $chatIds  Chat IDs to send to (null = all configured)
     * @param  string  $parseMode  Parse mode (HTML, Markdown, MarkdownV2)
     * @return bool Success status
     */
    public function sendMessage(string $message, ?array $chatIds = null, string $parseMode = 'HTML'): bool
    {
        $targetChatIds = $chatIds ?? TIntegrationSettings::get('telegram', 'chat_ids', []);

        if (TIntegrationSettings::get('telegram', 'send_mode', 'default') === 'external') {
            return $this->sendMessageViaExternalService($message, $targetChatIds);
        }

        $botToken = TIntegrationSettings::get('telegram', 'bot_token');

        if (empty($botToken) || empty($targetChatIds)) {
            \Log::warning('Telegram: bot_token or chat_ids not configured');

            return false;
        }

        $success = true;

        foreach ($targetChatIds as $chatId) {
            try {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                ]);

                if (! $response->successful()) {
                    \Log::error("Telegram send message failed for chat {$chatId}: ".$response->body());
                    $success = false;
                }
            } catch (\Exception $e) {
                \Log::error("Telegram send message error for chat {$chatId}: ".$e->getMessage());
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send message through an external relay service instead of the Telegram Bot API.
     *
     * The relay receives the bot token, chat id and message text and is responsible
     * for the actual Telegram call. Requests are authenticated with a protective
     * token via the Authorization header.
     *
     * @param  string  $message  Message text
     * @param  array  $chatIds  Chat IDs to send to
     * @return bool Success status
     */
    protected function sendMessageViaExternalService(string $message, array $chatIds): bool
    {
        $botToken = TIntegrationSettings::get('telegram', 'bot_token');
        $externalUrl = TIntegrationSettings::get('telegram', 'external_url');
        $externalToken = TIntegrationSettings::get('telegram', 'external_token');

        if (empty($externalUrl) || empty($botToken) || empty($chatIds)) {
            \Log::warning('Telegram (external): external_url, bot_token or chat_ids not configured');

            return false;
        }

        $success = true;

        foreach ($chatIds as $chatId) {
            try {
                $request = Http::acceptJson();

                if (! empty($externalToken)) {
                    $request = $request->withToken($externalToken);
                }

                $response = $request->post($externalUrl, [
                    'token' => $botToken,
                    'chat' => $chatId,
                    'message' => $message,
                ]);

                if (! $response->successful()) {
                    \Log::error("Telegram (external) send message failed for chat {$chatId}: ".$response->body());
                    $success = false;
                }
            } catch (\Exception $e) {
                \Log::error("Telegram (external) send message error for chat {$chatId}: ".$e->getMessage());
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send message with photo to Telegram
     *
     * @param  string  $photoUrl  URL of the photo
     * @param  string|null  $caption  Caption for the photo
     * @param  array|null  $chatIds  Chat IDs to send to (null = all configured)
     * @param  string  $parseMode  Parse mode (HTML, Markdown, MarkdownV2)
     * @return bool Success status
     */
    public function sendPhoto(string $photoUrl, ?string $caption = null, ?array $chatIds = null, string $parseMode = 'HTML'): bool
    {
        $botToken = TIntegrationSettings::get('telegram', 'bot_token');
        $targetChatIds = $chatIds ?? TIntegrationSettings::get('telegram', 'chat_ids', []);

        if (empty($botToken) || empty($targetChatIds)) {
            \Log::warning('Telegram: bot_token or chat_ids not configured');

            return false;
        }

        $success = true;

        foreach ($targetChatIds as $chatId) {
            try {
                $params = [
                    'chat_id' => $chatId,
                    'photo' => $photoUrl,
                ];

                if ($caption) {
                    $params['caption'] = $caption;
                    $params['parse_mode'] = $parseMode;
                }

                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendPhoto", $params);

                if (! $response->successful()) {
                    \Log::error("Telegram send photo failed for chat {$chatId}: ".$response->body());
                    $success = false;
                }
            } catch (\Exception $e) {
                \Log::error("Telegram send photo error for chat {$chatId}: ".$e->getMessage());
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send document to Telegram
     *
     * @param  string  $documentUrl  URL of the document
     * @param  string|null  $caption  Caption for the document
     * @param  array|null  $chatIds  Chat IDs to send to (null = all configured)
     * @return bool Success status
     */
    public function sendDocument(string $documentUrl, ?string $caption = null, ?array $chatIds = null): bool
    {
        $botToken = TIntegrationSettings::get('telegram', 'bot_token');
        $targetChatIds = $chatIds ?? TIntegrationSettings::get('telegram', 'chat_ids', []);

        if (empty($botToken) || empty($targetChatIds)) {
            \Log::warning('Telegram: bot_token or chat_ids not configured');

            return false;
        }

        $success = true;

        foreach ($targetChatIds as $chatId) {
            try {
                $params = [
                    'chat_id' => $chatId,
                    'document' => $documentUrl,
                ];

                if ($caption) {
                    $params['caption'] = $caption;
                }

                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendDocument", $params);

                if (! $response->successful()) {
                    \Log::error("Telegram send document failed for chat {$chatId}: ".$response->body());
                    $success = false;
                }
            } catch (\Exception $e) {
                \Log::error("Telegram send document error for chat {$chatId}: ".$e->getMessage());
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get bot info
     */
    public function getBotInfo(): ?array
    {
        $botToken = TIntegrationSettings::get('telegram', 'bot_token');

        if (empty($botToken)) {
            return null;
        }

        try {
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getMe");

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Telegram get bot info failed: '.$response->body());

            return null;
        } catch (\Exception $e) {
            \Log::error('Telegram get bot info error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Check if Telegram integration is configured
     */
    public function isConfigured(): bool
    {
        $botToken = TIntegrationSettings::get('telegram', 'bot_token');
        $chatIds = TIntegrationSettings::get('telegram', 'chat_ids', []);

        if (empty($botToken) || empty($chatIds)) {
            return false;
        }

        if (TIntegrationSettings::get('telegram', 'send_mode', 'default') === 'external') {
            return ! empty(TIntegrationSettings::get('telegram', 'external_url'));
        }

        return true;
    }

    /**
     * Test connection by sending test message
     *
     * @return array Result with success status and message
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Telegram не настроен. Укажите bot_token и chat_ids.',
            ];
        }

        $testMessage = "✅ Тестовое сообщение от AxoraCMS\n\nДата и время: ".now()->format('d.m.Y H:i:s');

        $result = $this->sendMessage($testMessage);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Тестовое сообщение успешно отправлено!',
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка при отправке сообщения. Проверьте логи.',
        ];
    }
}
