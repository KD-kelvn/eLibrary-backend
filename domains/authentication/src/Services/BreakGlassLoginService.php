<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Exceptions\AuthenticationException;

/**
 * Break-glass admin gate (FR-AUTH-06): password check then OTP challenge.
 */
class BreakGlassLoginService
{
    public function __construct(
        private readonly IdentifierLookupService $identifiers,
        private readonly OtpChallengeService $challenges,
    ) {}

    /**
     * @return array{challenge_id: string, channels: list<array{type: string, masked: string}>}
     */
    public function start(string $username, string $password, ?string $ip = null): array
    {
        $user = $this->identifiers->find($username);

        if (
            $user === null
            || ! Hash::check($password, $user->password)
            || ! $user->break_glass
            || ! $user->hasActiveRole('admin')
            || ! $user->canAuthenticate()
        ) {
            // Identical denial for unknown / wrong password / non-flagged (FR-AUTH-05/06).
            throw AuthenticationException::invalidCredentials();
        }

        return $this->challenges->createBreakGlassForUser($user, $ip);
    }
}
