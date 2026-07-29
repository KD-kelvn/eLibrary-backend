<?php

namespace Modules\BookBorrowing\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;

trait HandlesApiResponses
{
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message,
    ): JsonResponse {
        return $this->successResponse([
            'items' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $message);
    }

    protected function resourceResponse(
        JsonResource $resource,
        string $message,
        int $code = 200,
    ): JsonResponse {
        return $this->successResponse($resource, $message, $code);
    }

    protected function handleBookBorrowingException(BookBorrowingException $exception): JsonResponse
    {
        $code = $exception->getCode();

        if ($code === 404) {
            return $this->failResponse(null, $exception->getMessage(), 404);
        }

        if (in_array($code, [403, 422], true)) {
            return $this->failResponse(null, $exception->getMessage(), $code);
        }

        return $this->errorResponse($exception->getMessage(), $code ?: 500, $exception);
    }
}
