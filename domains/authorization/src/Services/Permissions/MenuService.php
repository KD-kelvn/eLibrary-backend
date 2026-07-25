<?php

namespace Modules\Authorization\Services\Permissions;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\MenuItem;
use Modules\Authorization\Models\MenuItemRole;
use Modules\Authorization\Models\MenuRole;
use Modules\Authorization\Repositories\Permissions\MenuRepository;

class MenuService
{
    public function __construct(private readonly MenuRepository $repository) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Menu
    {
        return $this->repository->find($id, true)
            ?? throw AuthorizationException::notFound('Menu');
    }

    public function store(array $data): Menu
    {
        $menu = $this->repository->create($data);
        AdminActivity::log('Menu created', $menu, ['code' => $menu->code], event: 'created');

        return $menu;
    }

    public function update(int $id, array $data): Menu
    {
        $menu = $this->repository->update($this->findMenu($id), $data);
        AdminActivity::log('Menu updated', $menu, ['changed' => array_keys($data)], event: 'updated');

        return $menu;
    }

    public function destroy(int $id): void
    {
        $menu = $this->findMenu($id);
        AdminActivity::log('Menu deleted', $menu, ['code' => $menu->code], event: 'deleted');
        $this->repository->delete($menu);
    }

    public function showItem(int $id): MenuItem
    {
        return $this->repository->findItem($id, true)
            ?? throw AuthorizationException::notFound('Menu item');
    }

    public function storeItem(array $data): MenuItem
    {
        return $this->repository->createItem($data);
    }

    public function updateItem(int $id, array $data): MenuItem
    {
        return $this->repository->updateItem($this->findItem($id), $data);
    }

    public function destroyItem(int $id): void
    {
        $this->repository->deleteItem($this->findItem($id));
    }

    public function assignMenuRole(array $data): MenuRole
    {
        if (MenuRole::query()->where($data)->exists()) {
            throw AuthorizationException::unprocessable('The role is already assigned to this menu.');
        }

        return MenuRole::query()->create($data);
    }

    public function removeMenuRole(int $id): void
    {
        $assignment = MenuRole::query()->find($id)
            ?? throw AuthorizationException::notFound('Menu role assignment');
        $assignment->delete();
    }

    public function assignMenuItemRole(array $data): MenuItemRole
    {
        if (MenuItemRole::query()->where($data)->exists()) {
            throw AuthorizationException::unprocessable('The role is already assigned to this menu item.');
        }

        return MenuItemRole::query()->create($data);
    }

    public function removeMenuItemRole(int $id): void
    {
        $assignment = MenuItemRole::query()->find($id)
            ?? throw AuthorizationException::notFound('Menu item role assignment');
        $assignment->delete();
    }

    private function findMenu(int $id): Menu
    {
        return $this->repository->find($id)
            ?? throw AuthorizationException::notFound('Menu');
    }

    private function findItem(int $id): MenuItem
    {
        return $this->repository->findItem($id)
            ?? throw AuthorizationException::notFound('Menu item');
    }
}
