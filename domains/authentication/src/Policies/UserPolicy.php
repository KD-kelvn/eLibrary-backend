<?php

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool { return $actor->hasActiveRole('admin'); }
    public function view(User $actor, User $user): bool { return $actor->hasActiveRole('admin'); }
    public function create(User $actor): bool { return $actor->hasActiveRole('admin'); }
    public function update(User $actor, User $user): bool { return $actor->hasActiveRole('admin'); }
    public function delete(User $actor, User $user): bool
    {
        return $actor->hasActiveRole('admin') && $actor->id !== $user->id;
    }
}
