<?php

namespace Modules\Authorization\Repositories\RolesManagement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;

class RolesRepository
{
    public function __construct(protected Role $model) {}

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
            ->when(
                filled($filters['status'] ?? null),
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Role
    {
        return $this->model->newQuery()
            ->with([
                'userRoles.user.profile',
                'userRoles.assignedBy.profile',
                'userRoles.revokedRole.revokedBy.profile',
                'revokedRoles.revokedBy.profile',
                'revokedRoles.userRole.user.profile',
            ])
            ->find($id);
    }

    public function create(array $attributes): Role
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role->refresh();
    }

    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    public function countActiveAssignments(Role $role): int
    {
        return $role->activeUserRoles()->count();
    }

    public function isActive(Role $role): bool
    {
        return $role->status === RoleStatusEnum::Active;
    }
}
