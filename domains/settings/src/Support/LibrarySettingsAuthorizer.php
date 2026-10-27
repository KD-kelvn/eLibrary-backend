<?php

namespace Modules\Settings\Support;

use Modules\Authentication\Models\User;
use Modules\Authorization\Services\PermissionChecker;

/**
 * FR-SET-08: view for librarian+admin; update/reset for admin.
 * Authoritative check is permission middleware (V1-004); this mirrors matrix codes.
 */
class LibrarySettingsAuthorizer
{
    public function __construct(
        protected PermissionChecker $permissions,
    ) {}

    public function canView(User $user): bool
    {
        return $this->permissions->allowsOne($user, 'settings.library.view');
    }

    public function canUpdate(User $user): bool
    {
        return $this->permissions->allowsOne($user, 'settings.library.update');
    }

    public function canReset(User $user): bool
    {
        return $this->permissions->allowsOne($user, 'settings.library.reset');
    }
}
