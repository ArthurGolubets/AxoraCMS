<?php

use Illuminate\Support\Facades\Route;
use HolartWeb\AxoraCMS\Http\Controllers\Integration\Exchange1cController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for external integrations (1C, etc.)
|
*/

// 1C Exchange routes (no authentication)
Route::match(['get', 'post'], '1c/exchange', [Exchange1cController::class, 'index']);
Route::match(['get', 'post'], '1c/test-exchange', [Exchange1cController::class, 'testExchange']);
