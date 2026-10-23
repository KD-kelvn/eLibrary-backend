<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Authentication\Models\User;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\SystemAction;

/**
 * Effective permission = active role ∩ user_system_actions grant (AccessContext model).
 */
class PermissionChecker
{
    public function allows(User $user, string ...$codes): bool
    {
        if ($codes === []) {
            return false;
        }

        foreach ($codes as $code) {
            if ($this->allowsOne($user, $code)) {
                return true;
            }
        }

        return false;
    }

    public function allowsOne(User $user, string $code): bool
    {
        $roleIds = $user
            ->activeUserRoles()
            ->whereHas('role', fn (Builder $query) => $query->where('status', RoleStatusEnum::Active->value))
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return false;
        }

        return SystemAction::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->where(function (Builder $access) use ($roleIds) {
                $access
                    ->whereIn('role_id', $roleIds)
                    ->orWhereHas(
                        'roles',
                        fn (Builder $rolesQuery) => $rolesQuery->whereKey($roleIds),
                    );
            })
            ->whereHas(
                'userGrants',
                fn (Builder $grants) => $grants->where('user_id', $user->id),
            )
            ->exists();
    }
}
