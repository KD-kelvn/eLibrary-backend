<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Http\Requests\IndexUserRequest;
use Modules\Authentication\Http\Requests\SetUserBlockedRequest;
use Modules\Authentication\Http\Requests\StoreManagedUserRequest;
use Modules\Authentication\Http\Requests\UpdateManagedUserRequest;
use Modules\Authentication\Http\Resources\ManagedUserResource;
use Modules\Authentication\Services\UserManagementService;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserManagementService $service) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return $this->successResponse([
            'items' => ManagedUserResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Users retrieved.');
    }

    public function show(string $user): JsonResponse
    {
        try {
            $model = $this->service->show((int) $user);
            $this->authorize('view', $model);

            return $this->successResponse(ManagedUserResource::make($model), 'User retrieved.');
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 404);
        }
    }

    public function store(StoreManagedUserRequest $request): JsonResponse
    {
        try {
            $user = $this->service->store($request->validated(), (int) $request->user()->id);

            return $this->successResponse(ManagedUserResource::make($user), 'User created.', 201);
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }

    public function update(UpdateManagedUserRequest $request, string $user): JsonResponse
    {
        try {
            return $this->successResponse(
                ManagedUserResource::make($this->service->update((int) $user, $request->validated())),
                'User updated.',
            );
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }

    public function setBlocked(SetUserBlockedRequest $request, string $user): JsonResponse
    {
        try {
            if ((int) $user === (int) $request->user()->id && $request->boolean('is_blocked')) {
                return $this->failResponse(null, 'You cannot block your own account.', 422);
            }

            return $this->successResponse(
                ManagedUserResource::make(
                    $this->service->setBlocked((int) $user, $request->boolean('is_blocked')),
                ),
                $request->boolean('is_blocked') ? 'User blocked.' : 'User unblocked.',
            );
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 422);
        }
    }

    public function destroy(string $user): JsonResponse
    {
        try {
            $model = $this->service->show((int) $user);
            $this->authorize('delete', $model);
            $this->service->destroy((int) $user);

            return $this->successResponse(null, 'User deleted.');
        } catch (AuthenticationException $exception) {
            return $this->failResponse(null, $exception->getMessage(), $exception->getCode() ?: 404);
        }
    }
}
