<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Repositories\RolesManagement\RolesRepository;

class RolePolicy
{
    use HandlesAuthorization;

    public function __construct(protected RolesRepository $repository) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Role $role): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Role $role): bool
    {
        return true;
    }

    public function delete(User $user, Role $role): bool
    {
        if (
            $this->repository->isActive($role)
            && $this->repository->countActiveAssignments($role) > 0
        ) {
            return false;
        }

        return true;
    }
}
