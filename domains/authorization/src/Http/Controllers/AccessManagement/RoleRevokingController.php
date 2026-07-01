<?php

namespace Modules\Authorization\Http\Controllers\AccessManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\AccessManagement\IndexRoleRevokingRequest;
use Modules\Authorization\Http\Requests\AccessManagement\StoreRoleRevokingRequest;
use Modules\Authorization\Http\Resources\RevokedRoleResource;
use Modules\Authorization\Services\AccessManagement\RoleRevokingService;
use Modules\Authorization\Traits\HandlesApiResponses;

class RoleRevokingController extends Controller
{
    use HandlesApiResponses;

    public function __construct(
        private readonly RoleRevokingService $roleRevokingService,
    ) {}

    public function index(IndexRoleRevokingRequest $request): JsonResponse
    {
        try {
            $paginator = $this->roleRevokingService->list(
                $request->validated(),
                (int) $request->input('per_page', 15),
            );

            return $this->paginatedResponse(
                $paginator,
                RevokedRoleResource::class,
                'Role revocations retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function show(string $role_revocation): JsonResponse
    {
        try {
            $revocation = $this->roleRevokingService->show((int) $role_revocation);
            $this->authorize('view', $revocation);

            return $this->resourceResponse(
                RevokedRoleResource::make($revocation),
                'Role revocation retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), 500, $exception);
        }
    }

    public function store(StoreRoleRevokingRequest $request): JsonResponse
    {
        try {
            $revocation = $this->roleRevokingService->store(
                $request->validated(),
                (int) $request->user()->id,
            );

            return $this->resourceResponse(
                RevokedRoleResource::make($revocation),
                'Role assignment revoked.',
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
}
