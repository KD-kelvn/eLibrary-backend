<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Support\Collection;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Repositories\BookDetailRepository;

class BookAuthorService
{
    public const DEFAULT_LIMIT = 20;

    public function __construct(
        protected BookDetailRepository $repository,
    ) {}

    /**
     * @param  array{search?: string|null, limit?: int|null}  $filters
     * @return Collection<int, BookDetail>
     */
    public function list(array $filters): Collection
    {
        return $this->repository->authors(
            $filters['search'] ?? null,
            (int) ($filters['limit'] ?? self::DEFAULT_LIMIT),
        );
    }
}
