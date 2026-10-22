<?php

namespace Modules\Authorization\Services\AccessManagement;

use App\Support\AdminActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;

class UserSystemActionService
{
    public function listForUser(int $userId): Collection
    {
        return UserSystemAction::query()
            ->where('user_id', $userId)
            ->with('systemAction:id,role_id,name,code,action_type,is_active')
            ->orderBy('id')
            ->get(['id', 'user_id', 'system_action_id', 'user_role_id', 'created_at', 'updated_at']);
    }

    public function assign(array $data): UserSystemAction
    {
        $user = User::query()->find($data['user_id'])
            ?? throw AuthorizationException::notFound('User');
        $action = SystemAction::query()->find($data['system_action_id'])
            ?? throw AuthorizationException::notFound('System action');

        $userRole = $this->resolveActiveUserRole($user, (int) $action->role_id);

        if ($grant = UserSystemAction::withTrashed()->where([
            'user_id' => $user->id,
            'system_action_id' => $action->id,
        ])->first()) {
            if (! $grant->trashed()) {
                throw AuthorizationException::unprocessable(
                    'This action is already assigned to the user.',
                );
            }

            $grant->restore();
            $grant->update(['user_role_id' => $userRole->id]);

            return $grant->load('systemAction:id,role_id,name,code,action_type,is_active');
        }

        $grant = UserSystemAction::query()->create([
            'user_id' => $user->id,
            'system_action_id' => $action->id,
            'user_role_id' => $userRole->id,
        ]);

        AdminActivity::log(
            'Action assigned to user',
            $grant,
            [
                'user_id' => $user->id,
                'system_action_id' => $action->id,
                'role_id' => $action->role_id,
            ],
            event: 'assigned',
        );

        return $grant->load('systemAction:id,role_id,name,code,action_type,is_active');
    }

    public function remove(int $id): void
    {
        $grant = UserSystemAction::query()->find($id)
            ?? throw AuthorizationException::notFound('User action assignment');

        AdminActivity::log(
            'Action revoked from user',
            $grant,
            [
                'user_id' => $grant->user_id,
                'system_action_id' => $grant->system_action_id,
            ],
            event: 'revoked',
        );

        $grant->delete();
    }

    /**
     * Replace the user's granted actions with the given set.
     * Every action must belong to one of the user's active roles.
     *
     * @param  list<int>  $actionIds
     * @return Collection<int, UserSystemAction>
     */
    public function syncForUser(int $userId, array $actionIds): Collection
    {
        $user = User::query()->find($userId)
            ?? throw AuthorizationException::notFound('User');

        $actionIds = collect($actionIds)->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($user, $actionIds) {
            $actions = SystemAction::query()
                ->whereIn('id', $actionIds)
                ->get()
                ->keyBy('id');

            if ($actions->count() !== $actionIds->count()) {
                throw AuthorizationException::unprocessable('One or more actions were not found.');
            }

            $roleIds = $user
                ->activeUserRoles()
                ->pluck('role_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($actions as $action) {
                if (! in_array((int) $action->role_id, $roleIds, true)) {
                    throw AuthorizationException::unprocessable(
                        "Action \"{$action->code}\" cannot be granted because the user does not hold its owning role.",
                    );
                }
            }

            $existing = UserSystemAction::withTrashed()
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('system_action_id');

            foreach ($existing as $grant) {
                if (! $actionIds->contains((int) $grant->system_action_id)) {
                    if (! $grant->trashed()) {
                        $grant->delete();
                    }

                    continue;
                }

                if ($grant->trashed()) {
                    $grant->restore();
                }

                $action = $actions->get((int) $grant->system_action_id);
                $userRole = $this->resolveActiveUserRole($user, (int) $action->role_id);
                $grant->update(['user_role_id' => $userRole->id]);
            }

            foreach ($actionIds as $actionId) {
                if ($existing->has($actionId)) {
                    continue;
                }

                $action = $actions->get($actionId);
                $userRole = $this->resolveActiveUserRole($user, (int) $action->role_id);

                UserSystemAction::query()->create([
                    'user_id' => $user->id,
                    'system_action_id' => $actionId,
                    'user_role_id' => $userRole->id,
                ]);
            }

            AdminActivity::log(
                'User action grants synced',
                $user,
                ['action_ids' => $actionIds->all()],
                event: 'updated',
            );

            return $this->listForUser($user->id);
        });
    }

    public function revokeForUserRole(UserRole $assignment): void
    {
        UserSystemAction::query()
            ->where('user_id', $assignment->user_id)
            ->where(function ($query) use ($assignment) {
                $query
                    ->where('user_role_id', $assignment->id)
                    ->orWhereHas(
                        'systemAction',
                        fn ($actions) => $actions->where('role_id', $assignment->role_id),
                    );
            })
            ->get()
            ->each(fn (UserSystemAction $grant) => $grant->delete());
    }

    private function resolveActiveUserRole(User $user, int $roleId): UserRole
    {
        $userRole = $user
            ->activeUserRoles()
            ->where('role_id', $roleId)
            ->first();

        if (! $userRole) {
            throw AuthorizationException::unprocessable(
                "The user must hold the action's role before actions can be assigned.",
            );
        }

        return $userRole;
    }
}
