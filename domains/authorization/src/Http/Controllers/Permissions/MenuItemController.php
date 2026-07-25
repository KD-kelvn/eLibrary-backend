<?php

namespace Modules\Authorization\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Http\Requests\Permissions\StoreMenuItemRequest;
use Modules\Authorization\Http\Requests\Permissions\UpdateMenuItemRequest;
use Modules\Authorization\Http\Resources\MenuItemResource;
use Modules\Authorization\Services\Permissions\MenuService;
use Modules\Authorization\Traits\HandlesApiResponses;

class MenuItemController extends Controller
{
    use HandlesApiResponses;

    public function __construct(private readonly MenuService $service) {}

    public function show(string $menu_item): JsonResponse
    {
        try {
            $item = $this->service->showItem((int) $menu_item);
            $this->authorize('view', $item);

            return $this->resourceResponse(MenuItemResource::make($item), 'Menu item retrieved.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        return $this->resourceResponse(
            MenuItemResource::make($this->service->storeItem($request->validated())),
            'Menu item created.',
            201,
        );
    }

    public function update(UpdateMenuItemRequest $request, string $menu_item): JsonResponse
    {
        try {
            return $this->resourceResponse(
                MenuItemResource::make($this->service->updateItem((int) $menu_item, $request->validated())),
                'Menu item updated.',
            );
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }

    public function destroy(string $menu_item): JsonResponse
    {
        try {
            $item = $this->service->showItem((int) $menu_item);
            $this->authorize('delete', $item);
            $this->service->destroyItem((int) $menu_item);

            return $this->successResponse(null, 'Menu item deleted.');
        } catch (AuthorizationException $exception) {
            return $this->handleAuthorizationException($exception);
        }
    }
}
