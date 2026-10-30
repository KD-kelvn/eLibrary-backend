<?php

namespace Modules\Authentication\Services;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;
use Modules\Settings\Services\SettingsService;

/**
 * LP-S5 / FR-AUTH-09 — role-scoped idle timeout via token last_used_at.
 */
class SessionIdleService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Most restrictive (shortest) idle window among the user's active roles.
     */
    public function idleTimeoutMinutesFor(User $user): int
    {
        $codes = $user->activeUserRoles()
            ->with('role:id,code')
            ->get()
            ->pluck('role.code')
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return (int) $this->settings->get('sessions.idle_timeout_min', 'student');
        }

        return (int) $codes
            ->map(fn (string $code) => (int) $this->settings->get('sessions.idle_timeout_min', $code))
            ->min();
    }

    /**
     * Used by Sanctum's authenticateAccessTokensUsing callback (before last_used_at updates).
     */
    public function assertAccessTokenNotIdle(SanctumPersonalAccessToken $accessToken, bool $isValid): bool
    {
        if (! $isValid) {
            return false;
        }

        $tokenable = $accessToken->tokenable;

        if (! $tokenable instanceof User) {
            return true;
        }

        $minutes = $this->idleTimeoutMinutesFor($tokenable);
        $anchor = $accessToken->last_used_at ?? $accessToken->created_at;

        if ($anchor === null) {
            return true;
        }

        if ($anchor->greaterThan(now()->subMinutes($minutes))) {
            return true;
        }

        if ($accessToken instanceof PersonalAccessToken || method_exists($accessToken, 'delete')) {
            $accessToken->delete();
        }

        throw AuthenticationException::sessionIdle();
    }
}
