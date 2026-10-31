<?php

namespace Modules\Integrations\Services;

use Modules\Integrations\Exceptions\IntegrationsException;
use Modules\Settings\Models\ProviderSetting;

/**
 * HMAC auth for university push (FR-SYNC-01).
 * Signature: X-Signature = sha256=<hex> over "{timestamp}.{rawBody}".
 */
class UniversityHmacVerifier
{
    public const GROUP = 'university';

    public function secret(): string
    {
        $stored = ProviderSetting::query()->where('group', self::GROUP)->first();
        $config = is_array($stored?->config) ? $stored->config : [];
        $secret = (string) ($config['hmac_secret'] ?? config('services.university.hmac_secret', ''));

        return $secret;
    }

    public function toleranceSeconds(): int
    {
        return (int) config('services.university.timestamp_tolerance_seconds', 300);
    }

    /**
     * @throws IntegrationsException
     */
    public function assertValid(?string $signature, ?string $timestamp, string $rawBody): void
    {
        $secret = $this->secret();
        if ($secret === '') {
            throw IntegrationsException::unauthorized('University sync HMAC secret is not configured.');
        }

        if ($signature === null || $signature === '' || $timestamp === null || $timestamp === '') {
            throw IntegrationsException::unauthorized('Missing signature headers.');
        }

        if (! ctype_digit($timestamp)) {
            throw IntegrationsException::unauthorized('Invalid timestamp.');
        }

        $ts = (int) $timestamp;
        $skew = abs(now()->getTimestamp() - $ts);
        if ($skew > $this->toleranceSeconds()) {
            throw IntegrationsException::unauthorized('Stale timestamp.');
        }

        $provided = $signature;
        if (str_starts_with(strtolower($provided), 'sha256=')) {
            $provided = substr($provided, 7);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        if (! hash_equals($expected, $provided)) {
            throw IntegrationsException::unauthorized('Invalid signature.');
        }
    }
}
