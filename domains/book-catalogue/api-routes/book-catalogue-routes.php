<?php

use Illuminate\Support\Facades\Route;
use Modules\BookCatalogue\Http\Controllers\Reusables\CategoryController;
use Modules\BookCatalogue\Http\Controllers\Reusables\ReusableOptionsController;
use Modules\BookCatalogue\Http\Controllers\Reusables\ShelfController;
use Modules\BookCatalogue\Http\Controllers\Reusables\SubCategoryController;
use Modules\BookCatalogue\Http\Controllers\Reusables\TagController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reusable-options', [ReusableOptionsController::class, 'index']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('sub-categories', SubCategoryController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('shelves', ShelfController::class);
});
