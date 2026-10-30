<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Bitrix24;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Calls the Bitrix24 REST API through an inbound webhook.
 *
 * The webhook address works as a password, so it is stored encrypted and is
 * removed from every error message.
 */
class Bitrix24Client
{
    public const INTEGRATION = 'bitrix24';

    public const WEBHOOK_PATTERN = '#^https://[^/\s]+/rest/\d+/[A-Za-z0-9_]+/?$#';

    private const TIMEOUT_SECONDS = 15;

    private const LIMIT_RETRIES = 3;

    private const LIMIT_ERRORS = ['QUERY_LIMIT_EXCEEDED', 'OPERATION_TIME_LIMIT'];

    public function __construct(private ?string $webhookUrl = null)
    {
        $this->webhookUrl ??= self::storedWebhook();
    }

    public static function storedWebhook(): ?string
    {
        $encrypted = TIntegrationSettings::get(self::INTEGRATION, 'webhook_url');

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function storeWebhook(string $url): void
    {
        TIntegrationSettings::set(self::INTEGRATION, 'webhook_url', Crypt::encryptString(trim($url)));
    }

    /**
     * The address as the admin panel may show it: portal and user, without the secret key.
     */
    public static function maskedWebhook(?string $url): ?string
    {
        return $url ? preg_replace('#(/rest/\d+/).+$#', '$1••••••/', $url) : null;
    }

    public function isConfigured(): bool
    {
        return (bool) $this->webhookUrl;
    }

    /**
     * Call one REST method and return its "result".
     *
     * @param  array<string, mixed>  $params
     *
     * @throws Bitrix24Exception
     */
    public function call(string $method, array $params = []): mixed
    {
        return $this->request($method, $params)['result'] ?? null;
    }

    /**
     * Call several methods in one request, which costs one hit of the rate limit.
     *
     * @param  array<string, string>  $commands  key => "method?query"
     * @return array<string, mixed> key => result
     *
     * @throws Bitrix24Exception
     */
    public function batch(array $commands): array
    {
        $response = $this->call('batch', ['halt' => 0, 'cmd' => $commands]);
        $errors = (array) ($response['result_error'] ?? []);

        if ($errors !== []) {
            $first = reset($errors);

            throw new Bitrix24Exception($this->describe($first['error'] ?? '', $first['error_description'] ?? ''));
        }

        return (array) ($response['result'] ?? []);
    }

    /**
     * Read every page of a list method.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     *
     * @throws Bitrix24Exception
     */
    public function all(string $method, array $params = [], int $maxPages = 20): array
    {
        $rows = [];
        $start = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->request($method, $params + ['start' => $start]);
            $rows = array_merge($rows, (array) ($response['result'] ?? []));

            if (! isset($response['next'])) {
                break;
            }

            $start = (int) $response['next'];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws Bitrix24Exception
     */
    private function request(string $method, array $params): array
    {
        if (! $this->webhookUrl) {
            throw new Bitrix24Exception('Не указан адрес вебхука Битрикс24');
        }

        $url = rtrim($this->webhookUrl, '/').'/'.$method.'.json';

        for ($attempt = 0; ; $attempt++) {
            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)->acceptJson()->asJson()->post($url, $params);
            } catch (ConnectionException) {
                throw new Bitrix24Exception('Не удалось подключиться к порталу Битрикс24: '.self::maskedWebhook($this->webhookUrl));
            }

            $data = $response->json();
            $code = is_array($data) ? (string) ($data['error'] ?? '') : '';

            if (in_array($code, self::LIMIT_ERRORS, true) && $attempt < self::LIMIT_RETRIES) {
                usleep(1_000_000 * ($attempt + 1));

                continue;
            }

            if ($code !== '') {
                throw new Bitrix24Exception($this->describe($code, (string) ($data['error_description'] ?? '')));
            }

            if (! $response->successful() || ! is_array($data)) {
                throw new Bitrix24Exception("Битрикс24 вернул ответ с кодом {$response->status()}");
            }

            return $data;
        }
    }

    private function describe(string $code, string $description): string
    {
        $text = match ($code) {
            'insufficient_scope', 'INSUFFICIENT_SCOPE' => 'У вебхука нет права «CRM (crm)». Добавьте его в настройках вебхука в Битрикс24',
            'INVALID_CREDENTIALS', 'invalid_token', 'NO_AUTH_FOUND' => 'Битрикс24 не принял вебхук: проверьте адрес и что вебхук не удалён',
            'QUERY_LIMIT_EXCEEDED' => 'Превышен лимит запросов к Битрикс24',
            default => trim($description) !== '' ? strip_tags($description) : $code,
        };

        return $text !== '' ? $text : 'Неизвестная ошибка Битрикс24';
    }
}
