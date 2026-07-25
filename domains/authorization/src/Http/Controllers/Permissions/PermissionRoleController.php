<?php

namespace Modules\Authorization\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\Permissions\StorePermissionRoleRequest;
use Modules\Authorization\Services\Permissions\MenuService;
use Modules\Authorization\Services\Permissions\SystemActionService;
use Modules\Authorization\Traits\HandlesApiResponses;

class PermissionRoleController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly MenuService $menus,
        private readonly SystemActionService $actions,
    ) {}

    public function storeMenuRole(StorePermissionRoleRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->menus->assignMenuRole($request->validated())->load(['menu', 'role']),
                'Role assigned to menu.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroyMenuRole(string $menu_role): JsonResponse
    {
        try {
            $this->menus->removeMenuRole((int) $menu_role);

            return $this->successResponse(null, 'Menu role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function storeMenuItemRole(StorePermissionRoleRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->menus->assignMenuItemRole($request->validated())->load(['menuItem', 'role']),
                'Role assigned to menu item.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroyMenuItemRole(string $menu_item_role): JsonResponse
    {
        try {
            $this->menus->removeMenuItemRole((int) $menu_item_role);

            return $this->successResponse(null, 'Menu item role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function storeSystemActionRole(StorePermissionRoleRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->actions->assignRole($request->validated())->load(['systemAction', 'role']),
                'Role assigned to system action.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroySystemActionRole(string $system_action_role): JsonResponse
    {
        try {
            $this->actions->removeRole((int) $system_action_role);

            return $this->successResponse(null, 'System action role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }
}
