<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\AuthController;
use Modules\Authentication\Http\Controllers\UserManagementController;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('otp/request', [AuthController::class, 'requestOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);

    Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::apiResource('users', UserManagementController::class);
        Route::patch('users/{user}/blocked', [UserManagementController::class, 'setBlocked']);
    });
});
