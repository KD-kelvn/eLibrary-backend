<?php

namespace Modules\BookBorrowing\Policies;

use Modules\Authentication\Models\User;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class PenaltyPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function view(User $user, PenaltyPolicy $policy): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, PenaltyPolicy $policy): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, PenaltyPolicy $policy): bool
    {
        return $user->hasActiveRole('admin') && ! $policy->is_active;
    }
}
