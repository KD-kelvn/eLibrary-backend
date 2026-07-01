<?php

namespace Modules\Authorization\Services\AccessManagement;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Repositories\AccessManagement\RoleAssignmentRepository;

class RoleAssignmentService
{
    public function __construct(protected RoleAssignmentRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): UserRole
    {
        $assignment = $this->repository->findByIdWithRelations($id);

        if (! $assignment) {
            throw AuthorizationException::notFound('Role assignment');
        }

        return $assignment;
    }

    public function store(array $data, int $assignedBy): UserRole
    {
        if ($this->repository->hasActiveAssignment($data['user_id'], $data['role_id'])) {
            throw AuthorizationException::unprocessable(
                'This user already has an active assignment for the selected role.',
            );
        }

        return $this->repository->create([
            'user_id' => $data['user_id'],
            'role_id' => $data['role_id'],
            'assigned_by' => $assignedBy,
            'expires_at' => $data['expires_at'] ?? null,
            'assigned_at' => now(),
        ]);
    }

    public function update(int $id, array $data): UserRole
    {
        $assignment = $this->findOrFail($id);

        if ($assignment->revokedRole) {
            throw AuthorizationException::unprocessable(
                'Cannot update a revoked role assignment.',
            );
        }

        $userId = $data['user_id'] ?? $assignment->user_id;
        $roleId = $data['role_id'] ?? $assignment->role_id;

        if ($this->repository->hasActiveAssignment($userId, $roleId, $assignment->id)) {
            throw AuthorizationException::unprocessable(
                'This user already has an active assignment for the selected role.',
            );
        }

        return $this->repository->update($assignment, collect($data)->only([
            'user_id',
            'role_id',
            'expires_at',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $assignment = $this->findOrFail($id);
        $this->repository->delete($assignment);
    }

    protected function findOrFail(int $id): UserRole
    {
        $assignment = $this->repository->findByIdWithRelations($id);

        if (! $assignment) {
            throw AuthorizationException::notFound('Role assignment');
        }

        return $assignment;
    }
}
