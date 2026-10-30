<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Authentication\Models\User;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemModule;

class AccessContextService
{
    /**
     * @return array{roles: Collection, actions: Collection, modules: Collection}
     */
    public function forUser(User $user): array
    {
        $assignments = $user
            ->activeUserRoles()
            ->whereHas('role', fn (Builder $query) => $query->where('status', RoleStatusEnum::Active->value))
            ->with('role')
            ->get();

        $roles = $assignments->pluck('role')->filter()->unique('id')->values();
        $roleIds = $roles->pluck('id');

        $actions = SystemAction::query()
            ->where('is_active', true)
            ->when(
                $roleIds->isEmpty(),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query
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
                    ),
            )
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'action_type', 'role_id', 'is_active']);

        $modules = SystemModule::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($roleIds) {
                $query
                    ->where('is_public', true)
                    ->when(
                        $roleIds->isNotEmpty(),
                        fn (Builder $builder) => $builder->orWhereHas(
                            'roles',
                            fn (Builder $rolesQuery) => $rolesQuery->whereIn('auth.roles.id', $roleIds),
                        ),
                    );
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return compact('roles', 'actions', 'modules');
    }
}
