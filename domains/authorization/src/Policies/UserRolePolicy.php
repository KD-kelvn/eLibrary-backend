<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\UserRole;

class UserRolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserRole $assignment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserRole $assignment): bool
    {
        return ! $assignment->revokedRole()->exists();
    }

    public function delete(User $user, UserRole $assignment): bool
    {
        return true;
    }
}
