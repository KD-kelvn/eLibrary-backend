<?php

namespace Modules\Authorization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\StoreSystemModuleRoleRequest;
use Modules\Authorization\Http\Resources\SystemModuleRoleResource;
use Modules\Authorization\Models\SystemModuleRole;
use Modules\Authorization\Services\SystemModuleRoleService;
use Modules\Authorization\Traits\HandlesApiResponses;

class SystemModuleRoleController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly SystemModuleRoleService $systemModuleRoleService,
    ) {}

    public function store(StoreSystemModuleRoleRequest $request): JsonResponse
    {
        try {
            $assignment = $this->systemModuleRoleService->store($request->validated());

            return $this->resourceResponse(
                SystemModuleRoleResource::make($assignment->load([
                    'systemModule',
                    'role',
                ])),
                'System module role assigned.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $system_module_role): JsonResponse
    {
        try {
            $assignment = SystemModuleRole::query()->findOrFail($system_module_role);
            $this->authorize('delete', $assignment);

            $this->systemModuleRoleService->destroy((int) $system_module_role);

            return $this->successResponse(null, 'System module role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
