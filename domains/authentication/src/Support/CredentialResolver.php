<?php

namespace Modules\Authentication\Support;

use Modules\Authentication\Enums\CredentialType;

class CredentialResolver
{
    public static function detect(string $identifier): CredentialType
    {
        $trimmed = trim($identifier);

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return CredentialType::Email;
        }

        $normalizer = app(PhoneNumberNormalizer::class);
        $asPhone = $normalizer->normalize($trimmed);

        if ($asPhone !== null && $normalizer->isE164($asPhone)) {
            // Prefer phone when input looks numeric / local mobile.
            if (preg_match('/^[\d\s+\-()]+$/', $trimmed) === 1) {
                return CredentialType::Phone;
            }
        }

        if (preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $trimmed) ?? '') === 1) {
            return CredentialType::Phone;
        }

        return CredentialType::Username;
    }

    public static function columnFor(CredentialType $type): string
    {
        return $type->value;
    }
}
