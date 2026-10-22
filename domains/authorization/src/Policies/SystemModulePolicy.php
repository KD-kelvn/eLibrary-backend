<?php

namespace Modules\Authorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemModule;

class SystemModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function view(User $user, SystemModule $module): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, SystemModule $module): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, SystemModule $module): bool
    {
        return $user->hasActiveRole('admin');
    }
}
