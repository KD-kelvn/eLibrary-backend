<?php

namespace Modules\Authorization\Services;

use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemPageRole;
use Modules\Authorization\Repositories\SystemPageRoleRepository;

class SystemPageRoleService
{
    public function __construct(protected SystemPageRoleRepository $repository) {}

    public function store(array $data): SystemPageRole
    {
        if ($this->repository->hasAssignment($data['system_page_id'], $data['role_id'])) {
            throw AuthorizationException::unprocessable(
                'This role is already assigned to the selected system page.',
            );
        }

        return $this->repository->create([
            'system_page_id' => $data['system_page_id'],
            'role_id' => $data['role_id'],
        ]);
    }

    public function destroy(int $id): void
    {
        $assignment = $this->repository->findById($id);

        if (! $assignment) {
            throw AuthorizationException::notFound('System page role assignment');
        }

        $this->repository->delete($assignment);
    }
}
