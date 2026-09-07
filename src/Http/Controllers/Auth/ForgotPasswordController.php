<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Show the form to request a password reset link.
     */
    public function showLinkRequestForm()
    {
        return view('axora-cms::auth.forgot-password');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Fire the reset regardless of outcome, then return an identical response
        // so the endpoint cannot be used to enumerate registered administrators.
        try {
            Password::broker('administrators')->sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $e) {
            Log::error('[admin] Не удалось отправить письмо для сброса пароля: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Если аккаунт с указанным email существует, на него отправлено письмо со ссылкой для сброса пароля.',
        ]);
    }
}
