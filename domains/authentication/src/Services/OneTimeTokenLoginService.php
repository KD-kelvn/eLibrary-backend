<?php

namespace Modules\Authentication\Services;

use App\Support\AdminActivity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\OneTimeToken;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\OneTimeTokenRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\GuardsAccountAccess;
use Modules\Authentication\Services\Concerns\IssuesApiTokens;
use Modules\Authentication\Services\Concerns\ResolvesAuthenticatedUser;
use Modules\Settings\Services\SettingsService;

class OneTimeTokenLoginService
{
    use GuardsAccountAccess;
    use IssuesApiTokens;
    use ResolvesAuthenticatedUser;

    public const GENERIC_CONTINUE = 'If an account matches, continue with the next step.';

    public const GENERIC_SENT = 'If an account matches, a code has been sent.';

    public const GENERIC_INVALID = 'The one-time code is invalid or has expired.';

    public function __construct(
        private readonly UserRepository $users,
        private readonly OneTimeTokenRepository $tokens,
        private readonly OtpNotifier $otpNotifier,
        private readonly OtpChallengeService $challenges,
        private readonly OtpRateLimiter $rateLimiter,
        private readonly SettingsService $settings,
    ) {}

    /**
     * FR-AUTH-01/02/05: start OTP sign-in — always returns a challenge envelope.
     *
     * @return array{challenge_id: string, channels: list<array{type: string, masked: string}>}
     */
    public function options(string $identifier, ?string $ip = null): array
    {
        $started = hrtime(true);

        $result = $this->challenges->createForIdentifier($identifier, $ip);

        $this->padTiming($started);

        return $result;
    }

    /**
     * FR-AUTH-02/03/04/08: send OTP for a challenge + channel (decoy-safe).
     *
     * @param  array<string, mixed>  $data
     * @return array{channel: string|null, expires_at: string|null, code: string|null}
     */
    public function requestForChallenge(array $data): array
    {
        $started = hrtime(true);

        $challenge = $this->challenges->find((string) $data['challenge_id']);
        if ($challenge === null) {
            $this->padTiming($started);
            throw AuthenticationException::challengeNotFound();
        }

        $channel = isset($data['channel']) ? strtolower((string) $data['channel']) : null;
        $ip = $data['ip_address'] ?? null;

        try {
            $this->rateLimiter->assertCanSend((string) $challenge['identifier'], $ip);
        } catch (AuthenticationException $e) {
            $this->padTiming($started);
            throw $e;
        }

        $user = $this->challenges->resolveUser($challenge);
        $eligible = $user instanceof User
            && $user->canAuthenticate()
            && $channel !== null
            && $this->challenges->channelAllowed($challenge, $channel);

        $expiresAt = null;
        $plainToken = null;
        $viaValue = null;

        if ($eligible) {
            $via = $this->challenges->mapChannelToVia($channel);
            $viaValue = $channel === 'phone' ? 'sms' : $channel;
            $purpose = OneTimeTokenPurpose::Login;

            $length = (int) $this->settings->get('otp.length');
            $expiryMinutes = (int) $this->settings->get('otp.expiry_min');
            $maxAttempts = (int) $this->settings->get('otp.max_attempts');

            $this->tokens->revokeActiveForUser($user, $purpose);

            $plainToken = $this->generatePlainToken($length);
            $expiresAt = now()->addMinutes($expiryMinutes);

            $this->tokens->create([
                'user_id' => $user->id,
                'challenge_id' => $challenge['id'],
                'token' => Hash::make($plainToken),
                'via' => $via->value,
                'expires_at' => $expiresAt,
                'purpose' => $purpose->value,
                'ip_address' => $ip,
                'user_agent' => $data['user_agent'] ?? null,
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
                'code_length' => $length,
                'expiry_minutes' => $expiryMinutes,
            ]);

            $this->otpNotifier->send($user, $plainToken, $via, $purpose, $expiryMinutes);
        } else {
            // Burn rate-limit budget and timing for decoys / inactive (FR-AUTH-05/08).
            $this->simulateSendWork();
        }

        $this->padTiming($started);

        return array_filter([
            'channel' => $viaValue ?? $channel,
            'expires_at' => $expiresAt?->toIso8601String(),
            'code' => config('authentication.otp.expose_in_response') && $plainToken !== null
                ? $plainToken
                : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * FR-AUTH-03/04: verify challenge-scoped OTP and issue Sanctum token.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function verifyForChallenge(array $data): array
    {
        $challenge = $this->challenges->find((string) $data['challenge_id']);
        if ($challenge === null) {
            throw AuthenticationException::invalidToken();
        }

        $code = (string) ($data['code'] ?? $data['token'] ?? '');
        $user = $this->challenges->resolveUser($challenge);

        if (! $user instanceof User || ! $user->canAuthenticate()) {
            $this->simulateVerifyWork();
            throw AuthenticationException::invalidToken();
        }

        $token = $this->findMatchingToken($user, $challenge['id'], $code, OneTimeTokenPurpose::Login);

        if ($token === null) {
            $this->registerFailedAttempt($user, $challenge['id']);
            throw AuthenticationException::invalidToken();
        }

        $this->tokens->markAsUsed($token);
        $this->clearFailedAttempts($user);
        $this->challenges->forget($challenge['id']);

        return $this->issueToken($user->load('profile'), $data['device_name'] ?? null);
    }

    /**
     * Legacy identifier+via issuance (password-reset until V1-012).
     *
     * @param  array<string, mixed>  $data
     */
    public function requestToken(array $data): array
    {
        $via = OneTimeTokenVia::from($data['via']);
        $purpose = isset($data['purpose'])
            ? OneTimeTokenPurpose::from($data['purpose'])
            : OneTimeTokenPurpose::Login;

        $user = $this->resolveUserForOneTimeTokenChannel($this->users, $data['identifier'], $via);
        $this->assertCanAuthenticate($user);

        $this->tokens->revokeActiveForUser($user, $purpose);

        $length = (int) ($this->safeSetting('otp.length') ?? config('authentication.otp.length', 6));
        $expiryMinutes = (int) ($this->safeSetting('otp.expiry_min') ?? config('authentication.otp.expiry_minutes', 10));
        $plainToken = $this->generatePlainToken($length);
        $expiresAt = now()->addMinutes($expiryMinutes);

        $this->tokens->create([
            'user_id' => $user->id,
            'token' => Hash::make($plainToken),
            'via' => $via->value,
            'expires_at' => $expiresAt,
            'purpose' => $purpose->value,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'attempts' => 0,
            'max_attempts' => (int) ($this->safeSetting('otp.max_attempts') ?? 5),
            'code_length' => $length,
            'expiry_minutes' => $expiryMinutes,
        ]);

        $this->otpNotifier->send($user, $plainToken, $via, $purpose, $expiryMinutes);

        return array_filter([
            'via' => $via->value,
            'purpose' => $purpose->value,
            'expires_at' => $expiresAt->toIso8601String(),
            'code' => config('authentication.otp.expose_in_response') ? $plainToken : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * Legacy identifier+token login (kept for transitional clients).
     *
     * @param  array<string, mixed>  $data
     */
    public function login(array $data): array
    {
        if (! empty($data['challenge_id'])) {
            return $this->verifyForChallenge($data);
        }

        $purpose = isset($data['purpose'])
            ? OneTimeTokenPurpose::from($data['purpose'])
            : OneTimeTokenPurpose::Login;

        $user = $this->resolveUserByIdentifier($this->users, $data['identifier']);
        $this->assertCanAuthenticate($user);

        $token = $this->findValidToken($user, (string) ($data['token'] ?? $data['code'] ?? ''), $purpose);

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

    private function findMatchingToken(
        User $user,
        string $challengeId,
        string $plainToken,
        OneTimeTokenPurpose $purpose,
    ): ?OneTimeToken {
        $candidates = $this->tokens->activeForChallenge($challengeId, $purpose);

        if ($candidates->isEmpty()) {
            $candidates = $this->tokens->activeForUser($user, $purpose);
        }

        foreach ($candidates as $storedToken) {
            if (Hash::check($plainToken, $storedToken->token)) {
                return $storedToken;
            }
        }

        return null;
    }

    private function registerFailedAttempt(User $user, string $challengeId): void
    {
        $active = $this->tokens->activeForChallenge($challengeId, OneTimeTokenPurpose::Login);
        $maxAttempts = (int) $this->settings->get('otp.max_attempts');

        foreach ($active as $stored) {
            $attempts = (int) $stored->attempts + 1;
            $cap = (int) ($stored->max_attempts ?? $maxAttempts);
            $stored->update(['attempts' => $attempts]);

            if ($attempts >= $cap) {
                $this->tokens->markAsUsed($stored);
            }
        }

        $lockAfter = (int) $this->settings->get('auth.lockout_after');
        $lockMin = (int) $this->settings->get('auth.lockout_min');
        $failKey = 'otp-fail-user:'.$user->id;
        $failures = (int) Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $failures, now()->addMinutes(max($lockMin, 60)));

        if ($failures >= $lockAfter) {
            $until = now()->addMinutes($lockMin);
            $user->forceFill(['locked_until' => $until])->save();
            Cache::forget($failKey);

            AdminActivity::log(
                'Account locked after failed OTP attempts',
                $user,
                [
                    'locked_until' => $until->toIso8601String(),
                    'failures' => $failures,
                ],
                logName: 'auth',
                event: 'auth.locked',
            );
        }
    }

    private function clearFailedAttempts(User $user): void
    {
        Cache::forget('otp-fail-user:'.$user->id);

        if ($user->locked_until !== null) {
            $user->forceFill(['locked_until' => null])->save();
        }
    }

    private function generatePlainToken(int $length): string
    {
        $length = max(4, min(10, $length));

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    private function padTiming(int $startedHrtime): void
    {
        $elapsedMs = (hrtime(true) - $startedHrtime) / 1_000_000;
        $targetMs = 80.0;

        if ($elapsedMs < $targetMs) {
            usleep((int) (($targetMs - $elapsedMs) * 1000));
        }
    }

    private function simulateSendWork(): void
    {
        // Approximate hash + notify cost so decoy timing matches real sends (FR-AUTH-05).
        Hash::make(str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
        usleep(5_000);
    }

    private function simulateVerifyWork(): void
    {
        Hash::check('000000', Hash::make('000000'));
    }

    private function safeSetting(string $key): mixed
    {
        try {
            return $this->settings->get($key);
        } catch (\Throwable) {
            return null;
        }
    }
}
