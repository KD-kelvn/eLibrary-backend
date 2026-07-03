<?php

namespace Modules\Authorization\Repositories;

use Modules\Authorization\Models\SystemModuleRole;

class SystemModuleRoleRepository
{
    public function __construct(protected SystemModuleRole $model) {}

    public function findById(int $id): ?SystemModuleRole
    {
        return $this->model->newQuery()->find($id);
    }

    public function hasAssignment(int $systemModuleId, int $roleId): bool
    {
        return $this->model->newQuery()
            ->where('system_module_id', $systemModuleId)
            ->where('role_id', $roleId)
            ->exists();
    }

    public function create(array $attributes): SystemModuleRole
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function delete(SystemModuleRole $assignment): bool
    {
        return (bool) $assignment->delete();
    }
}
