<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\Integrations\Http\Controllers\SyncRunController;
use Modules\Integrations\Http\Controllers\UniversityStudentPushController;

Route::post('university/students', [UniversityStudentPushController::class, 'store'])
    ->middleware(['university.hmac', 'throttle:60,1']);

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class, 'permission'])->group(function () {
    Route::get('sync-runs', [SyncRunController::class, 'index']);
    Route::get('sync-runs/{syncRun}', [SyncRunController::class, 'show']);
});
