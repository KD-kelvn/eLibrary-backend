<?php

namespace Modules\Authentication\Policies;

use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;

class PersonalAccessTokenPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasActiveRole('admin');
    }

    public function revoke(User $actor, PersonalAccessToken $token): bool
    {
        return $actor->hasActiveRole('admin');
    }

    public function revokeForUser(User $actor): bool
    {
        return $actor->hasActiveRole('admin');
    }
}
