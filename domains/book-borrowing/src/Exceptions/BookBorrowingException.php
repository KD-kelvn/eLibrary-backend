<?php

namespace Modules\BookBorrowing\Exceptions;

use App\Support\ApiErrorCode;
use Exception;

class BookBorrowingException extends Exception
{
    public function __construct(
        string $message,
        int $code = 422,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, $code);
    }

    public static function notFound(string $resource = 'Resource'): self
    {
        return new self("{$resource} not found.", 404, ApiErrorCode::NOT_FOUND);
    }

    public static function forbidden(string $message, ?string $errorCode = ApiErrorCode::FORBIDDEN): self
    {
        return new self($message, 403, $errorCode);
    }

    public static function unprocessable(string $message, ?string $errorCode = null): self
    {
        return new self($message, 422, $errorCode);
    }
}
