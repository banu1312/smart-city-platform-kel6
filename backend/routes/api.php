<?php

use App\Http\Controllers\CitizenController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\TrafficController;
use Illuminate\Support\Facades\Route;

// NOTE: OAuth2 endpoints (/oauth/token, /oauth/introspect, /oauth/revoke,
// /oauth/clients, etc.) are registered automatically by Laravel Passport.

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'code' => 200,
        'message' => 'Smart City Platform API is running',
        'service' => 'backend',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::apiResource('citizens', CitizenController::class);
    Route::apiResource('environment-sensors', EnvironmentController::class);
    Route::apiResource('traffic-records', TrafficController::class);
});
