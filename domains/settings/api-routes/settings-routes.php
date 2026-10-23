<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\Settings\Http\Controllers\BrandingController;
use Modules\Settings\Http\Controllers\LibrarySettingsController;
use Modules\Settings\Http\Controllers\LoginSlideController;

Route::get('branding', [BrandingController::class, 'show']);
Route::get('login-slides', [LoginSlideController::class, 'index']);
Route::get('library/public', [LibrarySettingsController::class, 'publicIndex']);

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::post('branding', [BrandingController::class, 'update']);

    Route::get('login-slides/manage', [LoginSlideController::class, 'manage']);
    Route::post('login-slides', [LoginSlideController::class, 'store']);
    Route::post('login-slides/{loginSlide}', [LoginSlideController::class, 'update']);
    Route::delete('login-slides/{loginSlide}', [LoginSlideController::class, 'destroy']);

    Route::get('library', [LibrarySettingsController::class, 'index']);
    Route::put('library', [LibrarySettingsController::class, 'update']);
    Route::post('library/reset', [LibrarySettingsController::class, 'reset']);
    Route::delete('library/pending/{id}', [LibrarySettingsController::class, 'cancelPending']);
    Route::get('library/history', [LibrarySettingsController::class, 'history']);
});
