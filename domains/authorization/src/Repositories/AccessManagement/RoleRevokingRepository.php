<?php

namespace Modules\Authorization\Repositories\AccessManagement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Models\RevokedRole;
use Modules\Authorization\Models\UserRole;

class RoleRevokingRepository
{
    public function __construct(
        protected RevokedRole $model,
        protected UserRole $userRoleModel,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with([
                'revokedBy.profile',
                'userRole.user.profile',
                'userRole.role',
                'userRole.assignedBy.profile',
            ])
            ->when(
                filled($filters['user_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'userRole',
                    fn ($builder) => $builder->where('user_id', $filters['user_id']),
                ),
            )
            ->when(
                filled($filters['role_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'userRole',
                    fn ($builder) => $builder->where('role_id', $filters['role_id']),
                ),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?RevokedRole
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?RevokedRole
    {
        return $this->model->newQuery()
            ->with([
                'revokedBy.profile',
                'userRole.user.profile',
                'userRole.role',
                'userRole.assignedBy.profile',
                'userRole.revokedRole',
            ])
            ->find($id);
    }

    public function findAssignment(int $userRoleId): ?UserRole
    {
        return $this->userRoleModel->newQuery()
            ->with(['user.profile', 'role', 'assignedBy.profile', 'revokedRole'])
            ->find($userRoleId);
    }

    public function existsForAssignment(int $userRoleId): bool
    {
        return $this->model->newQuery()
            ->where('user_role_id', $userRoleId)
            ->exists();
    }

    public function create(array $attributes): RevokedRole
    {
        return $this->model->newQuery()->create($attributes);
    }
}
