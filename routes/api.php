<?php

use HolartWeb\AxoraCMS\Http\Controllers\Callback\CustomFormSubmitController;
use HolartWeb\AxoraCMS\Http\Controllers\Integration\Exchange1cController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for external integrations (1C, etc.).
| Authentication is HTTP Basic against t_commerceml_settings and is enforced
| inside the controller; CSRF is disabled for this group in bootstrap/app.php.
|
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::match(['get', 'post'], '1c/exchange', [Exchange1cController::class, 'index']);
});

// Custom forms ("Своя форма") submitted from the site. Regular web route:
// session + CSRF token required (use @csrf in the form or the X-CSRF-TOKEN header).
if (Schema::hasTable('t_custom_forms')) {
    Route::post('forms/{code}', [CustomFormSubmitController::class, 'store'])
        ->where('code', '[a-z0-9_]+')
        ->middleware('throttle:10,1')
        ->name('axora-cms.forms.submit');
}
