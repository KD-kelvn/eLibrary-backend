<?php

namespace Modules\Authorization\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\SystemModule;

class SystemModuleRepository
{
    public function __construct(protected SystemModule $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?SystemModule
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?SystemModule
    {
        return $this->model->newQuery()
            ->with([
                'roles',
                'systemModuleRoles.role',
            ])
            ->withCount('systemModuleRoles')
            ->find($id);
    }

    public function create(array $attributes): SystemModule
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(SystemModule $module, array $attributes): SystemModule
    {
        $module->update($attributes);

        return $module->refresh();
    }

    public function delete(SystemModule $module): bool
    {
        return (bool) $module->delete();
    }
}
