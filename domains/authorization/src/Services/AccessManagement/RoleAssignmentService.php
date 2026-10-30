<?php

namespace Modules\Authorization\Services\AccessManagement;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Services\SessionService;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Repositories\AccessManagement\RoleAssignmentRepository;
use Modules\Authorization\Services\LastAdminGuard;

class RoleAssignmentService
{
    public function __construct(
        protected RoleAssignmentRepository $repository,
        protected UserSystemActionService $userActions,
        protected RoleActionGrantService $grants,
        protected LastAdminGuard $lastAdmin,
        protected SessionService $sessions,
    ) {}

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

        return DB::transaction(function () use ($data, $assignedBy) {
            $assignment = $this->repository->create([
                'user_id' => $data['user_id'],
                'role_id' => $data['role_id'],
                'assigned_by' => $assignedBy,
                'expires_at' => $data['expires_at'] ?? null,
                'assigned_at' => now(),
            ]);

            $this->grants->grantDefaults($assignment);

            AdminActivity::log(
                'Role assigned to user',
                $assignment,
                [
                    'user_id' => $assignment->user_id,
                    'role_id' => $assignment->role_id,
                    'before' => null,
                    'after' => [
                        'user_id' => $assignment->user_id,
                        'role_id' => $assignment->role_id,
                    ],
                ],
                logName: 'authorization',
                event: 'role.assigned',
            );

            $this->sessions->rotateTokensForUserId((int) $assignment->user_id);

            return $assignment;
        });
    }

    public function update(int $id, array $data): UserRole
    {
        $assignment = $this->findOrFail($id);

        if ($assignment->revokedRole) {
            throw AuthorizationException::unprocessable(
                'Cannot update a revoked role assignment.',
            );
        }

        $previousUserId = (int) $assignment->user_id;
        $userId = $data['user_id'] ?? $assignment->user_id;
        $roleId = $data['role_id'] ?? $assignment->role_id;

        if ($this->repository->hasActiveAssignment($userId, $roleId, $assignment->id)) {
            throw AuthorizationException::unprocessable(
                'This user already has an active assignment for the selected role.',
            );
        }

        $updated = $this->repository->update($assignment, collect($data)->only([
            'user_id',
            'role_id',
            'expires_at',
        ])->all());

        $this->sessions->rotateTokensForUserId($previousUserId);
        if ((int) $userId !== $previousUserId) {
            $this->sessions->rotateTokensForUserId((int) $userId);
        }

        return $updated;
    }

    public function destroy(int $id): void
    {
        $assignment = $this->findOrFail($id);

        $this->lastAdmin->assertCanRevokeAdminAssignment($assignment);

        $userId = (int) $assignment->user_id;

        AdminActivity::log(
            'Role assignment deleted',
            $assignment,
            [
                'user_id' => $assignment->user_id,
                'role_id' => $assignment->role_id,
                'before' => [
                    'user_id' => $assignment->user_id,
                    'role_id' => $assignment->role_id,
                ],
                'after' => null,
            ],
            logName: 'authorization',
            event: 'role.revoked',
        );

        $this->userActions->revokeForUserRole($assignment);
        $this->repository->delete($assignment);
        $this->sessions->rotateTokensForUserId($userId);
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
