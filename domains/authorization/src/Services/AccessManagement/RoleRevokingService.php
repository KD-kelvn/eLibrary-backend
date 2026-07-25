<?php

namespace Modules\Authorization\Services\AccessManagement;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\RevokedRole;
use Modules\Authorization\Repositories\AccessManagement\RoleRevokingRepository;

class RoleRevokingService
{
    public function __construct(protected RoleRevokingRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): RevokedRole
    {
        $revocation = $this->repository->findByIdWithRelations($id);

        if (! $revocation) {
            throw AuthorizationException::notFound('Role revocation');
        }

        return $revocation;
    }

    public function store(array $data, int $revokedBy): RevokedRole
    {
        $assignment = $this->repository->findAssignment($data['user_role_id']);

        if (! $assignment) {
            throw AuthorizationException::notFound('Role assignment');
        }

        if ($this->repository->existsForAssignment($assignment->id)) {
            throw AuthorizationException::unprocessable(
                'This role assignment has already been revoked.',
            );
        }

        if (! $assignment->isActive()) {
            throw AuthorizationException::unprocessable(
                'Only active role assignments can be revoked.',
            );
        }

        return DB::transaction(function () use ($data, $revokedBy, $assignment) {
            $revocation = $this->repository->create([
                'user_role_id' => $assignment->id,
                'revoked_by' => $revokedBy,
                'revoked_at' => now(),
                'reason' => $data['reason'] ?? null,
            ])->load([
                'revokedBy.profile',
                'userRole.user.profile',
                'userRole.role',
                'userRole.assignedBy.profile',
            ]);

            AdminActivity::log(
                'Role assignment revoked',
                $revocation,
                [
                    'user_role_id' => $assignment->id,
                    'reason' => $data['reason'] ?? null,
                ],
                event: 'revoked',
            );

            return $revocation;
        });
    }
}
