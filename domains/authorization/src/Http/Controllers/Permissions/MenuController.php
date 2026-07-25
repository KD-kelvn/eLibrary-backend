<?php

namespace Modules\Authorization\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\Permissions\IndexMenuRequest;
use Modules\Authorization\Http\Requests\Permissions\StoreMenuRequest;
use Modules\Authorization\Http\Requests\Permissions\UpdateMenuRequest;
use Modules\Authorization\Http\Resources\MenuResource;
use Modules\Authorization\Services\Permissions\MenuService;
use Modules\Authorization\Traits\HandlesApiResponses;

class MenuController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly MenuService $service) {}

    public function index(IndexMenuRequest $request): JsonResponse
    {
        $paginator = $this->service->list(
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return $this->paginatedResponse($paginator, MenuResource::class, 'Menus retrieved.');
    }

    public function show(string $menu): JsonResponse
    {
        try {
            $model = $this->service->show((int) $menu);
            $this->authorize('view', $model);

            return $this->resourceResponse(MenuResource::make($model), 'Menu retrieved.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        return $this->resourceResponse(
            MenuResource::make($this->service->store($request->validated())),
            'Menu created.',
            201,
        );
    }

    public function update(UpdateMenuRequest $request, string $menu): JsonResponse
    {
        try {
            return $this->resourceResponse(
                MenuResource::make($this->service->update((int) $menu, $request->validated())),
                'Menu updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroy(string $menu): JsonResponse
    {
        try {
            $model = $this->service->show((int) $menu);
            $this->authorize('delete', $model);
            $this->service->destroy((int) $menu);

            return $this->successResponse(null, 'Menu deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }
}
