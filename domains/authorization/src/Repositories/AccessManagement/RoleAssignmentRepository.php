<?php

namespace Modules\Authorization\Repositories\AccessManagement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\UserRole;

class RoleAssignmentRepository
{
    public function __construct(protected UserRole $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['user.profile', 'role', 'assignedBy.profile', 'revokedRole'])
            ->when(
                ($filters['status'] ?? 'all') === 'active',
                fn ($query) => $query->active(),
            )
            ->when(
                ($filters['status'] ?? 'all') === 'expired',
                fn ($query) => $query->expired(),
            )
            ->when(
                filled($filters['user_id'] ?? null),
                fn ($query) => $query->where('user_id', $filters['user_id']),
            )
            ->when(
                filled($filters['role_id'] ?? null),
                fn ($query) => $query->where('role_id', $filters['role_id']),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?UserRole
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?UserRole
    {
        return $this->model->newQuery()
            ->with([
                'user.profile',
                'role',
                'assignedBy.profile',
                'revokedRole.revokedBy.profile',
            ])
            ->find($id);
    }

    public function hasActiveAssignment(int $userId, int $roleId, ?int $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->active()
            ->exists();
    }

    public function create(array $attributes): UserRole
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(UserRole $assignment, array $attributes): UserRole
    {
        $assignment->update($attributes);

        return $assignment->refresh();
    }

    public function delete(UserRole $assignment): bool
    {
        return (bool) $assignment->delete();
    }
}
