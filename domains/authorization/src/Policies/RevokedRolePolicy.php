<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\RevokedRole;

class RevokedRolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RevokedRole $revokedRole): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
