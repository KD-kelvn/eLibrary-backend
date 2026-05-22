<?php

namespace Modules\Authentication\Support;

use Modules\Authentication\Enums\CredentialType;

class CredentialResolver
{
    public static function detect(string $identifier): CredentialType
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return CredentialType::Email;
        }

        if (preg_match('/^\+?[0-9]{10,15}$/', $identifier)) {
            return CredentialType::Phone;
        }

        return CredentialType::Username;
    }

    public static function columnFor(CredentialType $type): string
    {
        return $type->value;
    }
}
