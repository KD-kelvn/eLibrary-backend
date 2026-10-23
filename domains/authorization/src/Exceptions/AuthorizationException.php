<?php

namespace Modules\Authorization\Exceptions;

use Exception;

class AuthorizationException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        protected ?string $errorCode = null,
    ) {
        parent::__construct($message, $code);
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public static function notFound(string $resource = 'Resource'): self
    {
        return new self("{$resource} not found.", 404);
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public static function unprocessable(string $message, ?string $errorCode = null): self
    {
        return new self($message, 422, $errorCode);
    }

    public static function lastAdmin(string $message): self
    {
        return new self($message, 422, 'LAST_ADMIN');
    }
}
