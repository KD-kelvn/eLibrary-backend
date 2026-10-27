<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Modules\Authentication\Models\User;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemModule;

class AccessContextService
{
    /**
     * @return array{roles: Collection, menus: Collection, actions: Collection, modules: Collection}
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

        // Used by both whereHas (Builder) and with() eager-load constraints (Relation).
        $itemAccess = function (Builder|Relation $query) use ($roleIds): void {
            $query
                ->where('is_active', true)
                ->where(function (Builder $accessQuery) use ($roleIds) {
                    $accessQuery
                        ->where('is_public', true)
                        ->when(
                            $roleIds->isNotEmpty(),
                            fn (Builder $builder) => $builder
                                ->orWhereHas(
                                    'roles',
                                    fn (Builder $rolesQuery) => $rolesQuery->whereIn('auth.roles.id', $roleIds),
                                )
                                ->orWhereHas(
                                    'menu.roles',
                                    fn (Builder $rolesQuery) => $rolesQuery->whereIn('auth.roles.id', $roleIds),
                                ),
                        );
                });
        };

        $menus = Menu::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($roleIds, $itemAccess) {
                $query
                    ->where('is_public', true)
                    ->orWhereHas('items', $itemAccess)
                    ->when(
                        $roleIds->isNotEmpty(),
                        fn (Builder $builder) => $builder->orWhereHas(
                            'roles',
                            fn (Builder $rolesQuery) => $rolesQuery->whereIn('auth.roles.id', $roleIds),
                        ),
                    );
            })
            ->with([
                'items' => function ($query) use ($itemAccess) {
                    $itemAccess($query);
                    $query->with('systemModule');
                },
            ])
            ->orderBy('sort_order')
            ->get();

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

        return compact('roles', 'menus', 'actions', 'modules');
    }
}
