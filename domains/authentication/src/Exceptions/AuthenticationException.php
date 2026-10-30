<?php

namespace Modules\Authentication\Exceptions;

use RuntimeException;

class AuthenticationException extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials provided.', 401);
    }

    public static function userNotFound(): self
    {
        return new self('User account not found.', 404);
    }

    public static function duplicateAttribute(string $attribute): self
    {
        return new self("The {$attribute} has already been taken.", 422);
    }

    public static function invalidToken(): self
    {
        return new self('The one-time token is invalid or has expired.', 401);
    }

    public static function tokenAlreadyUsed(): self
    {
        return new self('The one-time token has already been used.', 401);
    }

    public static function unsupportedCredentialChannel(string $via): self
    {
        return new self("The requested channel [{$via}] is not supported for this account.", 422);
    }

    public static function accountBlocked(): self
    {
        return new self('This account has been blocked from using the system.', 403);
    }

    public static function accountInactive(): self
    {
        return new self('This account is inactive.', 403);
    }

    public static function accountLocked(): self
    {
        return new self('This account is temporarily locked. Try again later.', 403);
    }

    public static function tooManyAttempts(): self
    {
        return new self('Too many attempts. Try again later.', 429);
    }

    public static function challengeNotFound(): self
    {
        return new self('The sign-in challenge is invalid or has expired.', 422);
    }

    public static function unprocessable(string $message): self
    {
        return new self($message, 422);
    }
}
