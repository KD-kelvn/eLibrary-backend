<?php

namespace Modules\Authentication\Services\Concerns;

use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;

trait GuardsAccountAccess
{
    protected function assertCanAuthenticate(User $user): void
    {
        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        if (! $user->is_active) {
            throw AuthenticationException::accountInactive();
        }

        if ($user->isLocked()) {
            throw AuthenticationException::accountLocked();
        }
    }
}
