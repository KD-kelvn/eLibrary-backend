<?php

namespace Modules\Authorization\Services\AccessManagement;

use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserRole;
use Modules\Authorization\Models\UserSystemAction;

/**
 * Grants every matrix/menu action linked to a role assignment (owner or pivot).
 */
class RoleActionGrantService
{
    public function grantDefaults(UserRole $assignment): void
    {
        $actionIds = SystemAction::query()
            ->where('is_active', true)
            ->where(function ($query) use ($assignment) {
                $query
                    ->where('role_id', $assignment->role_id)
                    ->orWhereHas(
                        'roles',
                        fn ($roles) => $roles->whereKey($assignment->role_id),
                    );
            })
            ->pluck('id');

        foreach ($actionIds as $actionId) {
            $grant = UserSystemAction::withTrashed()->where([
                'user_id' => $assignment->user_id,
                'system_action_id' => $actionId,
            ])->first();

            if ($grant) {
                if ($grant->trashed()) {
                    $grant->restore();
                }
                $grant->update(['user_role_id' => $assignment->id]);

                continue;
            }

            UserSystemAction::query()->create([
                'user_id' => $assignment->user_id,
                'system_action_id' => $actionId,
                'user_role_id' => $assignment->id,
            ]);
        }
    }

    /**
     * Re-sync grants for every non-revoked assignment (post matrix seed / V1-004).
     */
    public function syncAllActive(): int
    {
        $count = 0;

        UserRole::query()
            ->active()
            ->orderBy('id')
            ->each(function (UserRole $assignment) use (&$count) {
                $this->grantDefaults($assignment);
                $count++;
            });

        return $count;
    }
}
