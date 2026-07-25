<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\Settings\Http\Controllers\BrandingController;

Route::get('branding', [BrandingController::class, 'show']);

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::post('branding', [BrandingController::class, 'update']);
});
