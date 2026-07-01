<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleAssignmentController;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleRevokingController;
use Modules\Authorization\Http\Controllers\RolesManagement\RolesController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roles', RolesController::class);

    Route::apiResource('role-assignments', RoleAssignmentController::class);

    Route::get('role-revocations', [RoleRevokingController::class, 'index']);
    Route::get('role-revocations/{role_revocation}', [RoleRevokingController::class, 'show']);
    Route::post('role-revocations', [RoleRevokingController::class, 'store']);
});
