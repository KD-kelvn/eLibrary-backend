<?php

namespace App\Support;

/**
 * Machine-readable fail/error codes for API clients.
 * Prefer these over parsing human message strings.
 */
final class ApiErrorCode
{
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    public const FORBIDDEN = 'FORBIDDEN';

    public const NOT_FOUND = 'NOT_FOUND';

    public const VALIDATION = 'VALIDATION';

    public const RATE_LIMITED = 'RATE_LIMITED';

    public const LOAN_LIMIT = 'LOAN_LIMIT';

    public const UNPAID_FINES = 'UNPAID_FINES';

    public const BOOK_UNAVAILABLE = 'BOOK_UNAVAILABLE';

    public const DUPLICATE_REQUEST = 'DUPLICATE_REQUEST';

    public const NOT_PHYSICAL_BOOK = 'NOT_PHYSICAL_BOOK';

    public const INVALID_STATE = 'INVALID_STATE';

    private function __construct() {}
}
