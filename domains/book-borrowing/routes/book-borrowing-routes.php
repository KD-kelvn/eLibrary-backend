<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\BookBorrowing\Http\Controllers\BorrowingProcessController;
use Modules\BookBorrowing\Http\Controllers\BorrowingRequestController;
use Modules\BookBorrowing\Http\Controllers\LibrarianDashboardController;
use Modules\BookBorrowing\Http\Controllers\PenaltyBatchController;
use Modules\BookBorrowing\Http\Controllers\PenaltyPolicyController;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::get('librarian/dashboard', LibrarianDashboardController::class);

    Route::get('processes/options', [BorrowingProcessController::class, 'options']);
    Route::apiResource('processes', BorrowingProcessController::class)
        ->parameters(['processes' => 'borrowing_process']);

    Route::apiResource('penalty-policies', PenaltyPolicyController::class)
        ->parameters(['penalty-policies' => 'penalty_policy']);

    Route::get('requests', [BorrowingRequestController::class, 'index']);
    Route::get('requests/{borrowing_request}', [BorrowingRequestController::class, 'show']);
    Route::post('requests/{borrowing_request}/approve', [BorrowingRequestController::class, 'approve']);
    Route::post('requests/{borrowing_request}/reject', [BorrowingRequestController::class, 'reject']);
    Route::post('requests/{borrowing_request}/mark-returned', [BorrowingRequestController::class, 'markReturned']);
    Route::delete('requests/{borrowing_request}', [BorrowingRequestController::class, 'destroy']);

    Route::get('penalty-batches', [PenaltyBatchController::class, 'index']);
    Route::get('penalty-batches/{penalty_batch}', [PenaltyBatchController::class, 'show']);
    Route::post('penalty-batches/{penalty_batch}/stop', [PenaltyBatchController::class, 'stop']);
    Route::post('penalty-batches/{penalty_batch}/mark-paid', [PenaltyBatchController::class, 'markPaid']);
});
