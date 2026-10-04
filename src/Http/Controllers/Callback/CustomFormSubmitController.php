<?php

namespace HolartWeb\AxoraCMS\Http\Controllers\Callback;

use HolartWeb\AxoraCMS\Services\CustomFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public endpoint the site posts custom forms to:
 * POST route('axora-cms.forms.submit', $code).
 *
 * Responds with JSON for AJAX requests, otherwise redirects back with
 * session('axora_form_success') = ['code' => ..., 'message' => ...].
 */
class CustomFormSubmitController extends Controller
{
    public function store(Request $request, string $code, CustomFormService $forms): JsonResponse|RedirectResponse
    {
        $form = $forms->findForm($code);
        abort_unless($form, 404);

        $submission = $forms->submitFromRequest($form, $request);
        $message = $form->success_message ?: 'Спасибо! Ваша заявка отправлена.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'id' => $submission?->id,
            ], 201);
        }

        return back()->with('axora_form_success', ['code' => $form->code, 'message' => $message]);
    }
}
