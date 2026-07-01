<?php

namespace Modules\Authorization\Http\Controllers\AccessManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\AccessManagement\IndexRoleAssignmentRequest;
use Modules\Authorization\Http\Requests\AccessManagement\StoreRoleAssignmentRequest;
use Modules\Authorization\Http\Requests\AccessManagement\UpdateRoleAssignmentRequest;
use Modules\Authorization\Http\Resources\UserRoleResource;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Services\AccessManagement\RoleAssignmentService;
use Modules\Authorization\Traits\HandlesApiResponses;

class RoleAssignmentController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly RoleAssignmentService $roleAssignmentService,
    ) {}

    public function index(IndexRoleAssignmentRequest $request): JsonResponse
    {
        try {
            $paginator = $this->roleAssignmentService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                UserRoleResource::class,
                'Role assignments retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $role_assignment): JsonResponse
    {
        try {
            $assignment = $this->roleAssignmentService->show((int) $role_assignment);
            $this->authorize('view', $assignment);

            return $this->resourceResponse(
                UserRoleResource::make($assignment),
                'Role assignment retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreRoleAssignmentRequest $request): JsonResponse
    {
        try {
            $assignment = $this->roleAssignmentService->store(
                $request->validated(),
                (int) $request->user()->id,
            );

            return $this->resourceResponse(
                UserRoleResource::make($assignment->load([
                    'user.profile',
                    'role',
                    'assignedBy.profile',
                ])),
                'Role assignment created.',
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

    public function update(UpdateRoleAssignmentRequest $request, string $role_assignment): JsonResponse
    {
        try {
            $assignment = $this->roleAssignmentService->update(
                (int) $role_assignment,
                $request->validated(),
            );

            return $this->resourceResponse(
                UserRoleResource::make($assignment->load([
                    'user.profile',
                    'role',
                    'assignedBy.profile',
                    'revokedRole',
                ])),
                'Role assignment updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function destroy(string $role_assignment): JsonResponse
    {
        try {
            $assignment = UserRole::query()->findOrFail($role_assignment);
            $this->authorize('delete', $assignment);

            $this->roleAssignmentService->destroy((int) $role_assignment);

            return $this->successResponse(null, 'Role assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }
}
