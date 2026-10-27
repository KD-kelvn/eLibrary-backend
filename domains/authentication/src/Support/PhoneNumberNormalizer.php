<?php

namespace Modules\Authentication\Support;

/**
 * Normalise phone numbers to E.164 for Tanzania default country (+255).
 */
class PhoneNumberNormalizer
{
    public const DEFAULT_COUNTRY_CODE = '255';

    public function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $trimmed) ?? '';
        $digits = str_replace(' ', '', $digits);

        if (str_starts_with($digits, '+')) {
            $body = preg_replace('/\D/', '', substr($digits, 1)) ?? '';

            return $body !== '' ? '+'.$body : null;
        }

        $body = preg_replace('/\D/', '', $digits) ?? '';
        if ($body === '') {
            return null;
        }

        // Local TZ mobile: 07XXXXXXXX → +2557XXXXXXXX
        if (str_starts_with($body, '0') && strlen($body) >= 10) {
            return '+'.self::DEFAULT_COUNTRY_CODE.substr($body, 1);
        }

        // Already country code without +
        if (str_starts_with($body, self::DEFAULT_COUNTRY_CODE)) {
            return '+'.$body;
        }

        return '+'.self::DEFAULT_COUNTRY_CODE.$body;
    }

    public function isE164(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
    }
}
