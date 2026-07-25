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
        return $user->hasActiveRole('admin');
    }

    public function view(User $user, UserRole $assignment): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, UserRole $assignment): bool
    {
        return $user->hasActiveRole('admin') && ! $assignment->revokedRole()->exists();
    }

    public function delete(User $user, UserRole $assignment): bool
    {
        return $user->hasActiveRole('admin');
    }
}
