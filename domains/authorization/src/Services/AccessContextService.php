<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Modules\Authentication\Models\User;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Menu;
use Modules\Authorization\Models\SystemAction;

class AccessContextService
{
    /**
     * @return array{roles: Collection, menus: Collection, actions: Collection}
     */
    public function forUser(User $user): array
    {
        $assignments = $user->activeUserRoles()
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
            ->with(['items' => $itemAccess])
            ->orderBy('sort_order')
            ->get();

        $actions = SystemAction::query()
            ->where('is_active', true)
            ->when(
                $roleIds->isEmpty(),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->whereHas(
                    'roles',
                    fn (Builder $rolesQuery) => $rolesQuery->whereIn('auth.roles.id', $roleIds),
                ),
            )
            ->orderBy('code')
            ->get();

        return compact('roles', 'menus', 'actions');
    }
}
