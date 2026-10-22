<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemPage;

class SystemPagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function view(User $user, SystemPage $page): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, SystemPage $page): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, SystemPage $page): bool
    {
        return $user->hasActiveRole('admin');
    }
}
