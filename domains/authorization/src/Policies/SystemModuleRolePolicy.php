<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemModuleRole;

class SystemModuleRolePolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, SystemModuleRole $assignment): bool
    {
        return $user->hasActiveRole('admin');
    }
}
