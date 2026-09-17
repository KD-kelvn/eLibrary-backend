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
            ->with(['roles', 'systemModuleRoles.role'])
            ->withCount('systemModuleRoles')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }),
            )
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
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

        return $module->refresh()->load(['roles', 'systemModuleRoles.role']);
    }

    public function delete(SystemModule $module): bool
    {
        return (bool) $module->delete();
    }
}
