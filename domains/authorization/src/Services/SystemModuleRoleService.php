<?php

namespace Modules\Authorization\Services;

use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemModuleRole;
use Modules\Authorization\Repositories\SystemModuleRoleRepository;

class SystemModuleRoleService
{
    public function __construct(protected SystemModuleRoleRepository $repository) {}

    public function store(array $data): SystemModuleRole
    {
        if ($this->repository->hasAssignment($data['system_module_id'], $data['role_id'])) {
            throw AuthorizationException::unprocessable(
                'This role is already assigned to the selected system module.',
            );
        }

        return $this->repository->create([
            'system_module_id' => $data['system_module_id'],
            'role_id' => $data['role_id'],
        ]);
    }

    public function destroy(int $id): void
    {
        $assignment = $this->repository->findById($id);

        if (! $assignment) {
            throw AuthorizationException::notFound('System module role assignment');
        }

        $this->repository->delete($assignment);
    }
}
