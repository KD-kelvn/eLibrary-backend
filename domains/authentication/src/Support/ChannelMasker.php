<?php

namespace Modules\Authentication\Support;

/**
 * Mask phone / email for OTP channel choice (FR-AUTH-02).
 */
class ChannelMasker
{
    public function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $len = strlen($digits);

        if ($len < 6) {
            return str_repeat('*', max($len, 4));
        }

        $prefix = substr($digits, 0, min(4, $len - 3));
        $suffix = substr($digits, -3);

        return '+'.$prefix.str_repeat('*', max($len - strlen($prefix) - 3, 3)).$suffix;
    }

    public function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '' || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', strtolower($email), 2);
        $localMask = strlen($local) <= 1
            ? '*'
            : substr($local, 0, 1).str_repeat('*', max(strlen($local) - 1, 1));

        $domainParts = explode('.', $domain);
        $name = $domainParts[0] ?? '';
        $tld = count($domainParts) > 1 ? '.'.implode('.', array_slice($domainParts, 1)) : '';
        $domainMask = strlen($name) <= 1
            ? '*'
            : substr($name, 0, 1).str_repeat('*', max(strlen($name) - 1, 1));

        return $localMask.'@'.$domainMask.$tld;
    }
}
