<?php

namespace HolartWeb\AxoraCMS\Services\Integrations\Cdek;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * CDEK API v2. The secret is stored encrypted, the token lives in the cache
 * until shortly before it expires.
 */
class CdekClient
{
    public const INTEGRATION = 'cdek';

    private const LIVE_URL = 'https://api.cdek.ru/v2';

    private const TEST_URL = 'https://api.edu.cdek.ru/v2';

    private const TIMEOUT_SECONDS = 20;

    private string $clientId;

    private string $secret;

    private bool $testMode;

    /**
     * @param  array{client_id?: ?string, client_secret?: ?string, test_mode?: bool}|null  $credentials  unsaved values from the form
     */
    public function __construct(?array $credentials = null)
    {
        $this->clientId = (string) ($credentials['client_id'] ?? TIntegrationSettings::get(self::INTEGRATION, 'client_id', ''));
        $this->secret = (string) (($credentials['client_secret'] ?? null) ?: self::storedSecret());
        $this->testMode = (bool) ($credentials['test_mode'] ?? TIntegrationSettings::get(self::INTEGRATION, 'test_mode', false));
    }

    public static function storedSecret(): ?string
    {
        $encrypted = TIntegrationSettings::get(self::INTEGRATION, 'client_secret');

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function storeSecret(string $secret): void
    {
        TIntegrationSettings::set(self::INTEGRATION, 'client_secret', Crypt::encryptString($secret));
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->secret !== '';
    }

    /**
     * Get a fresh token: proves the account and the mode (test/live) are right.
     *
     * @throws CdekException
     */
    public function checkConnection(): void
    {
        Cache::forget($this->tokenKey());
        $this->token();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     *
     * @throws CdekException
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<mixed>
     *
     * @throws CdekException
     */
    public function post(string $path, array $body): array
    {
        return $this->send('post', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>
     */
    private function send(string $method, string $path, array $data): array
    {
        $response = $this->http($method, $path, $data);

        // The token may have been revoked before its expiry date.
        if ($response->status() === 401) {
            Cache::forget($this->tokenKey());
            $response = $this->http($method, $path, $data);
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        throw new CdekException($this->errorMessage($response), $response->serverError() || $response->status() === 429);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function http(string $method, string $path, array $data): Response
    {
        try {
            return Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->withToken($this->token())
                ->{$method}($this->baseUrl().'/'.ltrim($path, '/'), $data);
        } catch (ConnectionException) {
            throw new CdekException('Не удалось подключиться к СДЭК', true);
        }
    }

    private function token(): string
    {
        if (! $this->isConfigured()) {
            throw new CdekException('Не указаны Client ID и Client Secret СДЭК');
        }

        $cached = Cache::get($this->tokenKey());

        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->asForm()->acceptJson()->post($this->baseUrl().'/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->secret,
            ]);
        } catch (ConnectionException) {
            throw new CdekException('Не удалось подключиться к СДЭК', true);
        }

        $token = $response->json('access_token');

        if (! $response->successful() || ! $token) {
            throw new CdekException('СДЭК не принял Client ID и Client Secret. Проверьте их и режим работы (тестовый или боевой)', $response->serverError());
        }

        Cache::put($this->tokenKey(), $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 60)));

        return $token;
    }

    private function errorMessage(Response $response): string
    {
        $errors = $response->json('requests.0.errors') ?? $response->json('errors') ?? [];
        $messages = array_filter(array_column((array) $errors, 'message'));

        return $messages !== [] ? implode('; ', $messages) : "СДЭК вернул ответ с кодом {$response->status()}";
    }

    private function baseUrl(): string
    {
        return $this->testMode ? self::TEST_URL : self::LIVE_URL;
    }

    private function tokenKey(): string
    {
        return 'axora-cms:cdek:token:'.md5($this->clientId.'|'.$this->secret.'|'.(int) $this->testMode);
    }
}
