<?php

namespace Modules\BookCatalogue\Exceptions;

use Exception;

class BookCatalogueException extends Exception
{
    public static function notFound(string $resource = 'Resource'): self
    {
        return new self("{$resource} not found.", 404);
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public static function unprocessable(string $message): self
    {
        return new self($message, 422);
    }
}
