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
use Modules\Authentication\Services\Concerns\IssuesApiTokens;
use Modules\Authentication\Services\Concerns\ResolvesAuthenticatedUser;

class OneTimeTokenLoginService
{
    use IssuesApiTokens;
    use ResolvesAuthenticatedUser;

    public function __construct(
        private readonly UserRepository $users,
        private readonly OneTimeTokenRepository $tokens,
        private readonly OtpNotifier $otpNotifier,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function requestToken(array $data): array
    {
        $via = OneTimeTokenVia::from($data['via']);
        $purpose = isset($data['purpose'])
            ? OneTimeTokenPurpose::from($data['purpose'])
            : OneTimeTokenPurpose::Login;

        $user = $this->resolveUserForOneTimeTokenChannel($this->users, $data['identifier'], $via);

        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        $this->tokens->revokeActiveForUser($user, $purpose);

        $plainToken = $this->generatePlainToken();
        $expiryMinutes = (int) config('authentication.otp.expiry_minutes', 10);
        $expiresAt = now()->addMinutes($expiryMinutes);

        $this->tokens->create([
            'user_id' => $user->id,
            'token' => Hash::make($plainToken),
            'via' => $via->value,
            'expires_at' => $expiresAt,
            'purpose' => $purpose->value,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        $this->otpNotifier->send($user, $plainToken, $via);

        return array_filter([
            'via' => $via->value,
            'purpose' => $purpose->value,
            'expires_at' => $expiresAt->toIso8601String(),
            'code' => config('authentication.otp.expose_in_response') ? $plainToken : null,
        ], fn ($value) => $value !== null);
    }

    /** @param  array<string, mixed>  $data */
    public function login(array $data): array
    {
        $purpose = isset($data['purpose'])
            ? OneTimeTokenPurpose::from($data['purpose'])
            : OneTimeTokenPurpose::Login;

        $user = $this->resolveUserByIdentifier($this->users, $data['identifier']);

        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        $token = $this->findValidToken($user, $data['token'], $purpose);

        $this->tokens->markAsUsed($token);

        return $this->issueToken($user->load('profile'), $data['device_name'] ?? null);
    }

    private function findValidToken(User $user, string $plainToken, OneTimeTokenPurpose $purpose): OneTimeToken
    {
        foreach ($this->tokens->activeForUser($user, $purpose) as $storedToken) {
            if (Hash::check($plainToken, $storedToken->token)) {
                return $storedToken;
            }
        }

        throw AuthenticationException::invalidToken();
    }

    private function generatePlainToken(): string
    {
        $length = (int) config('authentication.otp.length', 6);

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
