<?php

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleAssignmentController;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleRevokingController;
use Modules\Authorization\Http\Controllers\RolesManagement\RolesController;
use Modules\Authorization\Http\Controllers\SystemModuleController;
use Modules\Authorization\Http\Controllers\SystemModuleRoleController;
use Modules\Authorization\Http\Controllers\SystemPageController;
use Modules\Authorization\Http\Controllers\SystemPageRoleController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roles', RolesController::class);

    Route::apiResource('role-assignments', RoleAssignmentController::class);

    Route::get('role-revocations', [RoleRevokingController::class, 'index']);
    Route::get('role-revocations/{role_revocation}', [RoleRevokingController::class, 'show']);
    Route::post('role-revocations', [RoleRevokingController::class, 'store']);

    Route::apiResource('system-modules', SystemModuleController::class);
    Route::apiResource('system-pages', SystemPageController::class);

    Route::post('system-module-roles', [SystemModuleRoleController::class, 'store']);
    Route::delete('system-module-roles/{system_module_role}', [SystemModuleRoleController::class, 'destroy']);

    Route::post('system-page-roles', [SystemPageRoleController::class, 'store']);
    Route::delete('system-page-roles/{system_page_role}', [SystemPageRoleController::class, 'destroy']);
});
