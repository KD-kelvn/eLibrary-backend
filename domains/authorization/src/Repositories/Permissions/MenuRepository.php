<?php

namespace Modules\Authorization\Repositories\Permissions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\MenuItem;

class MenuRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Menu::query()
            ->withCount('items')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->when(isset($filters['is_public']), fn ($query) => $query->where('is_public', $filters['is_public']))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function find(int $id, bool $withRelations = false): ?Menu
    {
        return Menu::query()
            ->when($withRelations, fn ($query) => $query
                ->with([
                    'roles',
                    'menuRoles.role',
                    'items.roles',
                    'items.menuItemRoles.role',
                    'items.actions.roles',
                    'items.actions.systemActionRoles.role',
                ])
                ->withCount('items'))
            ->find($id);
    }

    public function create(array $data): Menu
    {
        return Menu::query()->create($data);
    }

    public function update(Menu $menu, array $data): Menu
    {
        $menu->update($data);

        return $menu->refresh();
    }

    public function delete(Menu $menu): bool
    {
        return (bool) $menu->delete();
    }

    public function findItem(int $id, bool $withRelations = false): ?MenuItem
    {
        return MenuItem::query()
            ->when($withRelations, fn ($query) => $query->with([
                'menu',
                'roles',
                'menuItemRoles.role',
                'actions.roles',
                'actions.systemActionRoles.role',
            ]))
            ->find($id);
    }

    public function createItem(array $data): MenuItem
    {
        return MenuItem::query()->create($data);
    }

    public function updateItem(MenuItem $item, array $data): MenuItem
    {
        $item->update($data);

        return $item->refresh();
    }

    public function deleteItem(MenuItem $item): bool
    {
        return (bool) $item->delete();
    }
}
