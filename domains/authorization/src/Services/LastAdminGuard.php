<?php

namespace Modules\Authorization\Services;

use Modules\Authentication\Models\User;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;

/**
 * FR-RBAC-03 / TC-RBAC-03: the last active admin cannot lose admin access.
 */
class LastAdminGuard
{
    public function assertCanRevokeAdminAssignment(UserRole $assignment): void
    {
        $assignment->loadMissing('role');

        if ($assignment->role?->code !== 'admin') {
            return;
        }

        if (! $assignment->isActive()) {
            return;
        }

        if ($this->activeAdminAssignmentCount() <= 1) {
            throw AuthorizationException::lastAdmin(
                'Cannot revoke the last active System Admin role assignment.',
            );
        }
    }

    public function assertCanBlockOrDeactivate(User $user): void
    {
        if (! $user->hasActiveRole('admin')) {
            return;
        }

        if ($this->activeAdminUserCount() <= 1) {
            throw AuthorizationException::lastAdmin(
                'Cannot block or deactivate the last active System Admin.',
            );
        }
    }

    public function assertCanDeleteUser(User $user): void
    {
        $this->assertCanBlockOrDeactivate($user);
    }

    public function activeAdminAssignmentCount(): int
    {
        $adminRoleId = Role::query()->where('code', 'admin')->value('id');

        if (! $adminRoleId) {
            return 0;
        }

        return UserRole::query()
            ->where('role_id', $adminRoleId)
            ->active()
            ->whereHas('user', fn ($q) => $q->where('is_blocked', false))
            ->count();
    }

    public function activeAdminUserCount(): int
    {
        return User::query()
            ->where('is_blocked', false)
            ->whereHas('activeUserRoles', function ($query) {
                $query->whereHas('role', fn ($roles) => $roles->where('code', 'admin'));
            })
            ->count();
    }
}
