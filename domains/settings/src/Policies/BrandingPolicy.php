<?php

namespace Modules\Settings\Policies;

use Modules\Authentication\Models\User;
use Modules\Settings\Models\Branding;

class BrandingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Branding $branding): bool
    {
        return true;
    }

    public function update(User $user, Branding $branding): bool
    {
        return $user->hasActiveRole('admin');
    }
}
