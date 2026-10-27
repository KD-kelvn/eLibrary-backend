<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\Notifications\Http\Controllers\NotificationController;
use Modules\Notifications\Http\Controllers\NotificationPreferenceController;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class, 'permission'])->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/preferences', [NotificationPreferenceController::class, 'show']);
    Route::put('/preferences', [NotificationPreferenceController::class, 'update']);
    Route::post('/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/{notification}/read', [NotificationController::class, 'markRead']);
});
