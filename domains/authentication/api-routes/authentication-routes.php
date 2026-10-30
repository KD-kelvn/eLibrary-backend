<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\AuthController;
use Modules\Authentication\Http\Controllers\SessionController;
use Modules\Authentication\Http\Controllers\UserManagementController;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        // Product rate limits (FR-AUTH-04) are enforced in OtpRateLimiter; keep a coarse HTTP cap.
        Route::post('otp/options', [AuthController::class, 'otpOptions']);
        Route::post('otp/request', [AuthController::class, 'requestOtp']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('break-glass', [AuthController::class, 'breakGlass']);
    });

    Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class, 'permission'])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('sessions', [SessionController::class, 'indexMine']);
        Route::delete('sessions/others', [SessionController::class, 'destroyOthers']);
        Route::delete('sessions/{personalAccessToken}', [SessionController::class, 'destroyMine']);

        Route::get('admin/sessions/summary', [SessionController::class, 'summary']);
        Route::get('admin/sessions', [SessionController::class, 'index']);
        Route::delete('admin/sessions/by-user/{user}', [SessionController::class, 'destroyForUser']);
        Route::delete('admin/sessions/{personalAccessToken}', [SessionController::class, 'destroy']);

        Route::apiResource('users', UserManagementController::class)->except(['destroy']);
        Route::patch('users/{user}/blocked', [UserManagementController::class, 'setBlocked']);
        Route::patch('users/{user}/active', [UserManagementController::class, 'setActive']);
        Route::post('users/{user}/photo', [UserManagementController::class, 'storePhoto']);
    });
});
