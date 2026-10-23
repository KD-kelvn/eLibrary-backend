<?php

namespace Modules\Settings\Support;

use Modules\Authentication\Models\User;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserSystemAction;

/**
 * FR-SET-08: view for librarian+admin; update/reset for admin.
 * Prefers matrix action grants; falls back to role codes until V1-004 middleware.
 */
class LibrarySettingsAuthorizer
{
    public function canView(User $user): bool
    {
        return $this->hasAction($user, 'settings.library.view')
            || $user->hasActiveRole('admin', 'librarian');
    }

    public function canUpdate(User $user): bool
    {
        return $this->hasAction($user, 'settings.library.update')
            || $user->hasActiveRole('admin');
    }

    public function canReset(User $user): bool
    {
        return $this->hasAction($user, 'settings.library.reset')
            || $user->hasActiveRole('admin');
    }

    protected function hasAction(User $user, string $code): bool
    {
        try {
            $actionId = SystemAction::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->value('id');

            if (! $actionId) {
                return false;
            }

            return UserSystemAction::query()
                ->where('user_id', $user->id)
                ->where('system_action_id', $actionId)
                ->exists();
        } catch (\Throwable) {
            // Table/schema unavailable (e.g. sqlite unit tests) — use role fallback.
            return false;
        }
    }
}
