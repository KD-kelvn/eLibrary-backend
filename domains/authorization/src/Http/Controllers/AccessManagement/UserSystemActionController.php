<?php

namespace Modules\Authorization\Http\Controllers\AccessManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\AccessManagement\StoreUserSystemActionRequest;
use Modules\Authorization\Http\Requests\AccessManagement\SyncUserSystemActionsRequest;
use Modules\Authorization\Http\Resources\UserSystemActionResource;
use Modules\Authorization\Services\AccessManagement\UserSystemActionService;
use Modules\Authorization\Traits\HandlesApiResponses;

class UserSystemActionController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly UserSystemActionService $service) {}

    public function index(string $user): JsonResponse
    {
        try {
            return $this->successResponse(
                UserSystemActionResource::collection($this->service->listForUser((int) $user)),
                'User action assignments retrieved.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function store(StoreUserSystemActionRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                UserSystemActionResource::make($this->service->assign($request->validated())),
                'Action assigned to user.',
                201,
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function sync(SyncUserSystemActionsRequest $request, string $user): JsonResponse
    {
        try {
            return $this->successResponse(
                UserSystemActionResource::collection(
                    $this->service->syncForUser((int) $user, $request->validated('action_ids')),
                ),
                'User action assignments updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroy(string $user_system_action): JsonResponse
    {
        try {
            $this->service->remove((int) $user_system_action);

            return $this->successResponse(null, 'User action assignment deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }
}
