<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemPageRole;

class SystemPageRolePolicy
{
    use HandlesAuthorization;

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, SystemPageRole $assignment): bool
    {
        return true;
    }
}
