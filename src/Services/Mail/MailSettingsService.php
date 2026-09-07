<?php

namespace HolartWeb\AxoraCMS\Services\Mail;

use HolartWeb\AxoraCMS\Models\Integrations\TIntegrationSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Runtime SMTP configuration stored in t_integration_settings
 * (integration_type = "mail").
 *
 * Replaces the previous approach of rewriting the project's .env file over
 * HTTP. The password is stored encrypted; settings are pushed into the mail
 * config at application boot via apply().
 */
class MailSettingsService
{
    private const INTEGRATION_TYPE = 'mail';

    private const MASK = '********';

    /**
     * Keys persisted for the SMTP mailer.
     *
     * @var array<int, string>
     */
    private const KEYS = [
        'mail_host',
        'mail_port',
        'mail_encryption',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_from_name',
    ];

    /**
     * Current settings for the admin UI. The password is never returned in
     * clear text — a non-empty password is reported as a fixed mask.
     *
     * @return array{mail_host: string, mail_port: string, mail_encryption: string, mail_username: string, mail_password: string, mail_from_address: string, mail_from_name: string}
     */
    public function get(): array
    {
        $raw = $this->raw();

        return [
            'mail_host' => $raw['mail_host'],
            'mail_port' => $raw['mail_port'] !== '' ? $raw['mail_port'] : '587',
            'mail_encryption' => $raw['mail_encryption'] !== '' ? $raw['mail_encryption'] : 'tls',
            'mail_username' => $raw['mail_username'],
            'mail_password' => $raw['mail_password'] !== '' ? self::MASK : '',
            'mail_from_address' => $raw['mail_from_address'],
            'mail_from_name' => $raw['mail_from_name'],
        ];
    }

    /**
     * Persist validated settings. Expects the caller (controller / form request)
     * to have already validated types; this method additionally strips CR/LF and
     * normalises empty values.
     *
     * The password is only written when a real value (not the mask) is supplied,
     * so re-saving the form without touching the field keeps the stored secret.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        $string = static function (mixed $value): string {
            return str_replace(["\r", "\n"], '', trim((string) ($value ?? '')));
        };

        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_host', $string($data['mail_host'] ?? ''));
        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_port', $string($data['mail_port'] ?? ''));
        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_encryption', $string($data['mail_encryption'] ?? ''));
        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_username', $string($data['mail_username'] ?? ''));
        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_from_address', $string($data['mail_from_address'] ?? ''));
        TIntegrationSettings::set(self::INTEGRATION_TYPE, 'mail_from_name', $string($data['mail_from_name'] ?? ''));

        $password = $data['mail_password'] ?? null;

        if ($password !== null && $password !== '' && $password !== self::MASK) {
            TIntegrationSettings::set(
                self::INTEGRATION_TYPE,
                'mail_password',
                Crypt::encryptString($string($password)),
            );
        }

        $this->apply();
    }

    /**
     * Push the stored settings into the runtime mail configuration.
     * Safe to call when nothing is configured (it simply no-ops).
     */
    public function apply(): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $raw = $this->raw();
        $encryption = $raw['mail_encryption'] !== '' ? $raw['mail_encryption'] : null;
        $port = $raw['mail_port'] !== '' ? (int) $raw['mail_port'] : ($encryption === 'ssl' ? 465 : 587);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $raw['mail_host'],
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => $raw['mail_username'] !== '' ? $raw['mail_username'] : null,
            'mail.mailers.smtp.password' => $this->password(),
        ]);

        if ($raw['mail_from_address'] !== '') {
            config(['mail.from.address' => $raw['mail_from_address']]);
        }

        if ($raw['mail_from_name'] !== '') {
            config(['mail.from.name' => $raw['mail_from_name']]);
        }
    }

    /**
     * Attempt to open an SMTP connection with the stored settings, optionally
     * overridden by the given values (used by the "test" button before saving).
     *
     * @param  array<string, mixed>  $overrides
     * @return array{success: bool, message: string}
     */
    public function testConnection(array $overrides = []): array
    {
        $raw = array_merge($this->raw(), array_filter(
            $overrides,
            static fn ($value) => $value !== null,
        ));

        $host = str_replace(["\r", "\n"], '', trim((string) ($raw['mail_host'] ?? '')));

        if ($host === '') {
            return ['success' => false, 'message' => 'Не указан SMTP-хост.'];
        }

        $encryption = ($raw['mail_encryption'] ?? '') !== '' ? $raw['mail_encryption'] : null;
        $port = ($raw['mail_port'] ?? '') !== '' ? (int) $raw['mail_port'] : ($encryption === 'ssl' ? 465 : 587);

        $password = ($overrides['mail_password'] ?? null) !== null && $overrides['mail_password'] !== self::MASK
            ? (string) $overrides['mail_password']
            : $this->password();

        config([
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => ($raw['mail_username'] ?? '') !== '' ? $raw['mail_username'] : null,
            'mail.mailers.smtp.password' => $password,
        ]);

        try {
            Mail::purge('smtp');
            Mail::mailer('smtp')->getSymfonyTransport()->start();

            return ['success' => true, 'message' => 'SMTP-соединение успешно установлено.'];
        } catch (\Throwable $e) {
            Log::warning('SMTP connection test failed: '.$e->getMessage());

            return ['success' => false, 'message' => 'Не удалось подключиться к SMTP-серверу. Проверьте настройки.'];
        }
    }

    /**
     * Whether a usable SMTP host has been configured.
     */
    public function isConfigured(): bool
    {
        return $this->raw()['mail_host'] !== '';
    }

    /**
     * Raw stored values (password still encrypted), every key present as a string.
     *
     * @return array<string, string>
     */
    private function raw(): array
    {
        $stored = TIntegrationSettings::getAll(self::INTEGRATION_TYPE);

        $values = [];

        foreach (self::KEYS as $key) {
            $values[$key] = isset($stored[$key]) ? (string) $stored[$key] : '';
        }

        return $values;
    }

    /**
     * Decrypted SMTP password, or null when unset / undecryptable.
     */
    private function password(): ?string
    {
        $encrypted = $this->raw()['mail_password'];

        if ($encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            Log::warning('Stored SMTP password could not be decrypted.');

            return null;
        }
    }
}
