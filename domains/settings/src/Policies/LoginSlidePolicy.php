<?php

namespace Modules\Settings\Policies;

use Modules\Authentication\Models\User;
use Modules\Settings\Models\LoginSlide;

class LoginSlidePolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->hasActiveRole('admin') ?? false;
    }

    public function view(?User $user, LoginSlide $loginSlide): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function update(User $user, LoginSlide $loginSlide): bool
    {
        return $user->hasActiveRole('admin');
    }

    public function delete(User $user, LoginSlide $loginSlide): bool
    {
        return $user->hasActiveRole('admin');
    }
}
