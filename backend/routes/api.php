<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Currencies
        Route::get('/currencies',          [CurrencyController::class, 'index']);
        Route::get('/currencies/selected', [CurrencyController::class, 'selected']);
        Route::post('/currencies/selected',[CurrencyController::class, 'updateSelected']);
        Route::get('/currencies/rates',    [CurrencyController::class, 'liveRates']);

        // Reports
        Route::get('/reports',              [ReportController::class, 'index']);
        Route::post('/reports',             [ReportController::class, 'store']);
        Route::get('/reports/{reportRequest}', [ReportController::class, 'show']);
    });
});
