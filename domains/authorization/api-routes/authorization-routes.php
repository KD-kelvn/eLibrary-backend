<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleAssignmentController;
use Modules\Authorization\Http\Controllers\AccessManagement\RoleRevokingController;
use Modules\Authorization\Http\Controllers\RolesManagement\RolesController;
use Modules\Authorization\Http\Controllers\Permissions\MenuController;
use Modules\Authorization\Http\Controllers\Permissions\MenuItemController;
use Modules\Authorization\Http\Controllers\Permissions\PermissionRoleController;
use Modules\Authorization\Http\Controllers\Permissions\SystemActionController;
use Modules\Authorization\Http\Controllers\SystemModuleController;
use Modules\Authorization\Http\Controllers\SystemModuleRoleController;
use Modules\Authorization\Http\Controllers\SystemPageController;
use Modules\Authorization\Http\Controllers\SystemPageRoleController;
use Modules\Authorization\Http\Controllers\AdminDashboardController;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class])->group(function () {
    Route::get('admin-dashboard', AdminDashboardController::class);

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

    Route::apiResource('menus', MenuController::class);
    Route::post('menu-items', [MenuItemController::class, 'store']);
    Route::get('menu-items/{menu_item}', [MenuItemController::class, 'show']);
    Route::match(['put', 'patch'], 'menu-items/{menu_item}', [MenuItemController::class, 'update']);
    Route::delete('menu-items/{menu_item}', [MenuItemController::class, 'destroy']);

    Route::post('menu-roles', [PermissionRoleController::class, 'storeMenuRole'])->name('menu-roles.store');
    Route::delete('menu-roles/{menu_role}', [PermissionRoleController::class, 'destroyMenuRole']);
    Route::post('menu-item-roles', [PermissionRoleController::class, 'storeMenuItemRole'])->name('menu-item-roles.store');
    Route::delete('menu-item-roles/{menu_item_role}', [PermissionRoleController::class, 'destroyMenuItemRole']);

    Route::apiResource('system-actions', SystemActionController::class);
    Route::post('system-action-roles', [PermissionRoleController::class, 'storeSystemActionRole'])->name('system-action-roles.store');
    Route::delete('system-action-roles/{system_action_role}', [PermissionRoleController::class, 'destroySystemActionRole']);
});
