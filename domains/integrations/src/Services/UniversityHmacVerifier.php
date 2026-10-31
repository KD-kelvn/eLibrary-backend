<?php

namespace Modules\Integrations\Services;

use Modules\Integrations\Exceptions\IntegrationsException;

/**
 * HMAC auth for university push (FR-SYNC-01).
 * Signature: X-Signature = sha256=<hex> over "{timestamp}.{rawBody}".
 */
class UniversityHmacVerifier
{
    public function __construct(private readonly UniversityProviderConfig $config) {}

    public function secret(): string
    {
        return $this->config->pushAuth()['hmac_secret'];
    }

    public function toleranceSeconds(): int
    {
        return $this->config->pushAuth()['timestamp_tolerance_seconds'];
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
