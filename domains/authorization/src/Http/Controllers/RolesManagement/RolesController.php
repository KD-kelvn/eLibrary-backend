<?php

namespace Modules\Authorization\Http\Controllers\RolesManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\RolesManagement\IndexRoleRequest;
use Modules\Authorization\Http\Requests\RolesManagement\StoreRoleRequest;
use Modules\Authorization\Http\Requests\RolesManagement\UpdateRoleRequest;
use Modules\Authorization\Http\Resources\RoleResource;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Services\RolesManagement\RolesService;
use Modules\Authorization\Traits\HandlesApiResponses;

class RolesController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly RolesService $rolesService) {}

    public function index(IndexRoleRequest $request): JsonResponse
    {
        try {
            $paginator = $this->rolesService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse($paginator, RoleResource::class, 'Roles retrieved.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $role): JsonResponse
    {
        try {
            $this->authorize('view', Role::query()->findOrFail($role));

            return $this->resourceResponse(
                RoleResource::make($this->rolesService->show((int) $role)),
                'Role retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->rolesService->store($request->validated());

            return $this->resourceResponse(
                RoleResource::make($role),
                'Role created.',
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

    public function update(UpdateRoleRequest $request, string $role): JsonResponse
    {
        try {
            $updatedRole = $this->rolesService->update((int) $role, $request->validated());

            return $this->resourceResponse(
                RoleResource::make($updatedRole),
                'Role updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $role): JsonResponse
    {
        try {
            $roleModel = Role::query()->findOrFail($role);
            $this->authorize('delete', $roleModel);

            $this->rolesService->destroy((int) $role);

            return $this->successResponse(null, 'Role deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
