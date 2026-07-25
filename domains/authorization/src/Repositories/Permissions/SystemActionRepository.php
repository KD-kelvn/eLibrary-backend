<?php

namespace Modules\Authorization\Repositories\Permissions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\SystemAction;

class SystemActionRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return SystemAction::query()
            ->with(['menuItem', 'roles', 'systemActionRoles.role'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when(isset($filters['is_active']), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->when(filled($filters['menu_item_id'] ?? null), fn ($query) => $query->where('menu_item_id', $filters['menu_item_id']))
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id, bool $withRelations = false): ?SystemAction
    {
        return SystemAction::query()
            ->when($withRelations, fn ($query) => $query->with([
                'menuItem',
                'roles',
                'systemActionRoles.role',
            ]))
            ->find($id);
    }

    public function create(array $data): SystemAction
    {
        return SystemAction::query()->create($data);
    }

    public function update(SystemAction $action, array $data): SystemAction
    {
        $action->update($data);

        return $action->refresh();
    }

    public function delete(SystemAction $action): bool
    {
        return (bool) $action->delete();
    }
}
