<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Repositories\RolesManagement\RolesRepository;

class RolePolicy
{
    use HandlesAuthorization;

    public function __construct(
        protected RolesRepository $repository
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $user->hasActiveRole('admin')) {
            return false;
        }

        return $this->repository->countAssignments($role) === 0;
    }
}
