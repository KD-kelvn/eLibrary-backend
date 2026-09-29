<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\BookCatalogue\Http\Controllers\Reusables\CategoryController;
use Modules\BookCatalogue\Http\Controllers\Reusables\ReusableOptionsController;
use Modules\BookCatalogue\Http\Controllers\Reusables\ShelfController;
use Modules\BookCatalogue\Http\Controllers\Reusables\SubCategoryController;
use Modules\BookCatalogue\Http\Controllers\Reusables\TagController;
use Modules\BookCatalogue\Http\Controllers\BookAuthorController;
use Modules\BookCatalogue\Http\Controllers\BookDetailController;
use Modules\BookCatalogue\Http\Controllers\BookFavouriteController;
use Modules\BookCatalogue\Http\Controllers\BookPageController;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::get('reusable-options', [ReusableOptionsController::class, 'index']);

    Route::get('favourites', [BookFavouriteController::class, 'index']);
    Route::post('favourites', [BookFavouriteController::class, 'store']);
    Route::delete('favourites/{book}', [BookFavouriteController::class, 'destroy'])
        ->whereNumber('book');

    Route::get('authors', [BookAuthorController::class, 'index']);

    Route::get('books/summary', [BookDetailController::class, 'summary']);
    Route::get('books/{book}/related', [BookDetailController::class, 'related'])
        ->whereNumber('book');
    Route::get('books/{book}/download', [BookDetailController::class, 'download'])
        ->whereNumber('book');
    Route::get('books/{book}/read', [BookPageController::class, 'start'])
        ->whereNumber('book');
    Route::get('books/{book}/pages/{page}', [BookPageController::class, 'show'])
        ->whereNumber(['book', 'page']);
    Route::apiResource('books', BookDetailController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('sub-categories', SubCategoryController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('shelves', ShelfController::class);
});
