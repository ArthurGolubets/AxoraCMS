<?php

use HolartWeb\AxoraCMS\Http\Controllers\Integration\Exchange1cController;
use Illuminate\Support\Facades\Route;

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
