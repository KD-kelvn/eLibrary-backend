<?php

namespace Modules\Authorization\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\Permissions\StorePermissionRoleRequest;
use Modules\Authorization\Services\Permissions\SystemActionService;
use Modules\Authorization\Traits\HandlesApiResponses;

class PermissionRoleController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly SystemActionService $actions,
    ) {}

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
