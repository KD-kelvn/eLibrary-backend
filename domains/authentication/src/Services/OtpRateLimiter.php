<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\RateLimiter;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Settings\Services\SettingsService;

/**
 * Settings-driven OTP send / verify rate limits (FR-AUTH-04 / LP-S1..S4).
 */
class OtpRateLimiter
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function assertCanSend(string $normalizedIdentifier, ?string $ip): void
    {
        $perId = (int) $this->settings->get('otp.sends_per_identifier');
        $windowMin = (int) $this->settings->get('otp.window_min');
        $perIp = (int) $this->settings->get('otp.sends_per_ip_hour');

        $idKey = 'otp-send-id:'.$normalizedIdentifier;
        if (RateLimiter::tooManyAttempts($idKey, $perId)) {
            throw AuthenticationException::tooManyAttempts();
        }

        if ($ip !== null && $ip !== '') {
            $ipKey = 'otp-send-ip:'.$ip;
            if (RateLimiter::tooManyAttempts($ipKey, $perIp)) {
                throw AuthenticationException::tooManyAttempts();
            }
        }

        RateLimiter::hit($idKey, $windowMin * 60);

        if ($ip !== null && $ip !== '') {
            RateLimiter::hit('otp-send-ip:'.$ip, 3600);
        }
    }

    public function int(string $key): int
    {
        return (int) $this->settings->get($key);
    }
}
