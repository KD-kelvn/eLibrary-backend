<?php

namespace Modules\Authorization\Repositories;

use Modules\Authorization\Models\SystemPageRole;

class SystemPageRoleRepository
{
    public function __construct(protected SystemPageRole $model) {}

    public function findById(int $id): ?SystemPageRole
    {
        return $this->model->newQuery()->find($id);
    }

    public function hasAssignment(int $systemPageId, int $roleId): bool
    {
        return $this->model->newQuery()
            ->where('system_page_id', $systemPageId)
            ->where('role_id', $roleId)
            ->exists();
    }

    public function create(array $attributes): SystemPageRole
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function delete(SystemPageRole $assignment): bool
    {
        return (bool) $assignment->delete();
    }
}
