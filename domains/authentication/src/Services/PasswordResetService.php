<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\OneTimeToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\OneTimeTokenRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\ResolvesAuthenticatedUser;

class PasswordResetService
{
    use ResolvesAuthenticatedUser;

    public function __construct(
        private readonly UserRepository $users,
        private readonly OneTimeTokenRepository $tokens,
        private readonly OneTimeTokenLoginService $otpLogin,
    ) {}

    /**
     * Request a password-reset OTP for an email address that exists in records.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function request(array $data): array
    {
        return $this->otpLogin->requestToken([
            'identifier' => $data['identifier'],
            'via' => OneTimeTokenVia::Email->value,
            'purpose' => OneTimeTokenPurpose::PasswordReset->value,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * Validate the OTP without consuming it so the password form can retry safely.
     *
     * @param  array<string, mixed>  $data
     * @return array{verified: bool}
     */
    public function verifyOtp(array $data): array
    {
        $user = $this->resolveUserForOneTimeTokenChannel(
            $this->users,
            $data['identifier'],
            OneTimeTokenVia::Email,
        );

        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        $this->findValidToken($user, $data['token'], OneTimeTokenPurpose::PasswordReset);

        return ['verified' => true];
    }

    /**
     * Consume a valid password-reset OTP, set the new password, and revoke sessions.
     *
     * @param  array<string, mixed>  $data
     * @return array{reset: bool}
     */
    public function reset(array $data): array
    {
        $user = $this->resolveUserForOneTimeTokenChannel(
            $this->users,
            $data['identifier'],
            OneTimeTokenVia::Email,
        );

        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        $token = $this->findValidToken(
            $user,
            $data['token'],
            OneTimeTokenPurpose::PasswordReset,
        );

        $this->users->update($user, [
            'password' => $data['password'],
        ]);

        $this->tokens->markAsUsed($token);
        $this->tokens->revokeActiveForUser($user, OneTimeTokenPurpose::PasswordReset);

        // Invalidate every existing API session after a password change.
        $user->tokens()->delete();

        return ['reset' => true];
    }

    private function findValidToken(
        User $user,
        string $plainToken,
        OneTimeTokenPurpose $purpose,
    ): OneTimeToken {
        foreach ($this->tokens->activeForUser($user, $purpose) as $storedToken) {
            if (Hash::check($plainToken, $storedToken->token)) {
                return $storedToken;
            }
        }

        throw AuthenticationException::invalidToken();
    }
}
