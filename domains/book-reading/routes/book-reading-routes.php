<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\BookReading\Http\Controllers\ReadingHistoryController;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::post('reading-histories/track', [ReadingHistoryController::class, 'track']);
    Route::get('reading-histories', [ReadingHistoryController::class, 'index']);
    Route::get('reading-histories/{reading_history}', [ReadingHistoryController::class, 'show']);
});
