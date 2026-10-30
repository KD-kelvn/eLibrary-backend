<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Support\ChannelMasker;

/**
 * Short-lived OTP challenges (FR-AUTH-01/02/05). Stored in cache — decoys included.
 *
 * @phpstan-type ChallengeArray array{
 *     id: string,
 *     identifier: string,
 *     user_id: int|null,
 *     eligible: bool,
 *     break_glass: bool,
 *     channels: list<string>,
 *     ip: string|null,
 *     created_at: string
 * }
 */
class OtpChallengeService
{
    public const CACHE_PREFIX = 'otp_challenge:';

    public const TTL_MINUTES = 15;

    public function __construct(
        private readonly IdentifierLookupService $identifiers,
        private readonly ChannelMasker $masker,
    ) {}

    /**
     * @return array{challenge_id: string, channels: list<array{type: string, masked: string}>}
     */
    public function createForIdentifier(string $identifier, ?string $ip = null): array
    {
        $user = $this->identifiers->find($identifier);
        $eligible = $user instanceof User && $user->canAuthenticate();
        $channels = $eligible ? $this->availableChannels($user) : [];

        $id = (string) Str::uuid();
        $payload = [
            'id' => $id,
            'identifier' => $this->identifiers->normalize($identifier),
            'user_id' => $eligible ? $user->id : null,
            'eligible' => $eligible && $channels !== [],
            'break_glass' => false,
            'channels' => $channels,
            'ip' => $ip,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_PREFIX.$id, $payload, now()->addMinutes(self::TTL_MINUTES));

        return [
            'challenge_id' => $id,
            'channels' => $this->maskedChannels($user, $channels),
        ];
    }

    /**
     * FR-AUTH-06: challenge after successful break-glass password check.
     *
     * @return array{challenge_id: string, channels: list<array{type: string, masked: string}>}
     */
    public function createBreakGlassForUser(User $user, ?string $ip = null): array
    {
        $channels = $this->availableChannels($user);
        if ($channels === []) {
            throw AuthenticationException::unprocessable(
                'No OTP delivery channel is available for this account.',
            );
        }

        $id = (string) Str::uuid();
        $identifier = filled($user->username)
            ? (string) $user->username
            : (string) ($user->email ?? $user->phone);

        $payload = [
            'id' => $id,
            'identifier' => $this->identifiers->normalize($identifier),
            'user_id' => $user->id,
            'eligible' => true,
            'break_glass' => true,
            'channels' => $channels,
            'ip' => $ip,
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_PREFIX.$id, $payload, now()->addMinutes(self::TTL_MINUTES));

        return [
            'challenge_id' => $id,
            'channels' => $this->maskedChannels($user, $channels),
        ];
    }

    /** @return ChallengeArray|null */
    public function find(string $challengeId): ?array
    {
        $payload = Cache::get(self::CACHE_PREFIX.$challengeId);

        return is_array($payload) ? $payload : null;
    }

    public function forget(string $challengeId): void
    {
        Cache::forget(self::CACHE_PREFIX.$challengeId);
    }

    public function resolveUser(array $challenge): ?User
    {
        if (! ($challenge['eligible'] ?? false) || empty($challenge['user_id'])) {
            return null;
        }

        return User::query()->find($challenge['user_id']);
    }

    public function mapChannelToVia(string $channel): OneTimeTokenVia
    {
        return match (strtolower($channel)) {
            'sms', 'phone' => OneTimeTokenVia::Phone,
            'email' => OneTimeTokenVia::Email,
            default => OneTimeTokenVia::Email,
        };
    }

    public function channelAllowed(array $challenge, string $channel): bool
    {
        $via = $this->mapChannelToVia($channel);
        $type = $via === OneTimeTokenVia::Phone ? 'sms' : 'email';

        return in_array($type, $challenge['channels'] ?? [], true);
    }

    /** @return list<string> */
    protected function availableChannels(User $user): array
    {
        $channels = [];

        if (filled($user->phone)) {
            $channels[] = 'sms';
        }

        if (filled($user->email)) {
            $channels[] = 'email';
        }

        return $channels;
    }

    /**
     * @param  list<string>  $channels
     * @return list<array{type: string, masked: string}>
     */
    protected function maskedChannels(?User $user, array $channels): array
    {
        if ($user === null || $channels === []) {
            return [];
        }

        $out = [];

        foreach ($channels as $type) {
            $masked = $type === 'sms'
                ? $this->masker->maskPhone($user->phone)
                : $this->masker->maskEmail($user->email);

            if ($masked === null) {
                continue;
            }

            $out[] = ['type' => $type, 'masked' => $masked];
        }

        return $out;
    }
}
