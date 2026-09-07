<?php

namespace HolartWeb\AxoraCMS\Http\Controllers;

use HolartWeb\AxoraCMS\Services\Mail\MailSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Thin HTTP layer over {@see MailSettingsService}.
 *
 * The routes are still mounted at admin/api/environment* for SPA compatibility;
 * settings are persisted in the database (t_integration_settings), never in .env.
 */
class MailSettingsController extends Controller
{
    public function __construct(private readonly MailSettingsService $mailSettings) {}

    /**
     * Current SMTP settings (password masked).
     */
    public function index(): JsonResponse
    {
        return response()->json($this->mailSettings->get());
    }

    /**
     * Persist SMTP settings.
     */
    public function update(Request $request): JsonResponse
    {
        $noNewlines = 'regex:/^[^\r\n]*$/';

        $validated = $request->validate([
            'mail_host' => ['nullable', 'string', 'max:255', $noNewlines],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'mail_username' => ['nullable', 'string', 'max:255', $noNewlines],
            'mail_password' => ['nullable', 'string', 'max:255', $noNewlines],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255', $noNewlines],
        ]);

        $this->mailSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Настройки почты сохранены.',
        ]);
    }

    /**
     * Test an SMTP connection with the submitted (not yet saved) values.
     */
    public function testSmtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->mailSettings->testConnection($validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
