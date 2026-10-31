<?php

namespace Modules\Integrations\Exceptions;

use Exception;

class IntegrationsException extends Exception
{
    public static function unprocessable(string $message, int $code = 422): self
    {
        return new self($message, $code);
    }

    public static function unauthorized(string $message = 'Invalid signature.'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }
}
