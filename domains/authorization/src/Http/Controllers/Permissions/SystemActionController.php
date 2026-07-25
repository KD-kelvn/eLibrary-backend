<?php

namespace Modules\Authorization\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\Permissions\IndexSystemActionRequest;
use Modules\Authorization\Http\Requests\Permissions\StoreSystemActionRequest;
use Modules\Authorization\Http\Requests\Permissions\UpdateSystemActionRequest;
use Modules\Authorization\Http\Resources\SystemActionResource;
use Modules\Authorization\Services\Permissions\SystemActionService;
use Modules\Authorization\Traits\HandlesApiResponses;

class SystemActionController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly SystemActionService $service) {}

    public function index(IndexSystemActionRequest $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return $this->paginatedResponse($paginator, SystemActionResource::class, 'System actions retrieved.');
    }

    public function show(string $system_action): JsonResponse
    {
        try {
            $action = $this->service->show((int) $system_action);
            $this->authorize('view', $action);

            return $this->resourceResponse(SystemActionResource::make($action), 'System action retrieved.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function store(StoreSystemActionRequest $request): JsonResponse
    {
        return $this->resourceResponse(
            SystemActionResource::make($this->service->store($request->validated())),
            'System action created.',
            201,
        );
    }

    public function update(UpdateSystemActionRequest $request, string $system_action): JsonResponse
    {
        try {
            return $this->resourceResponse(
                SystemActionResource::make($this->service->update((int) $system_action, $request->validated())),
                'System action updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroy(string $system_action): JsonResponse
    {
        try {
            $action = $this->service->show((int) $system_action);
            $this->authorize('delete', $action);
            $this->service->destroy((int) $system_action);

            return $this->successResponse(null, 'System action deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }
}
