<?php

namespace Modules\BookCatalogue\Repositories;

use Illuminate\Support\Collection;
use Modules\BookCatalogue\Models\BookFavourite;

class BookFavouriteRepository
{
    public function __construct(
        protected BookFavourite $model,
    ) {}

    /**
     * @return Collection<int, BookFavourite>
     */
    public function listForUser(int $userId, int $limit = 12): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->where('user_id', $userId)
            ->with([
                'bookDetail.categories',
                'bookDetail.tags',
                'bookDetail.digitalBook',
            ])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function findForUserBook(int $userId, int $bookDetailId): ?BookFavourite
    {
        return $this
            ->model
            ->newQuery()
            ->where('user_id', $userId)
            ->where('book_detail_id', $bookDetailId)
            ->first();
    }

    public function findTrashedForUserBook(int $userId, int $bookDetailId): ?BookFavourite
    {
        return $this
            ->model
            ->newQuery()
            ->onlyTrashed()
            ->where('user_id', $userId)
            ->where('book_detail_id', $bookDetailId)
            ->first();
    }

    public function existsForUserBook(int $userId, int $bookDetailId): bool
    {
        return $this
            ->model
            ->newQuery()
            ->where('user_id', $userId)
            ->where('book_detail_id', $bookDetailId)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function allBookIdsForUser(int $userId): array
    {
        return $this
            ->model
            ->newQuery()
            ->where('user_id', $userId)
            ->pluck('book_detail_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $bookDetailIds
     * @return list<int>
     */
    public function favouriteBookIdsForUser(int $userId, array $bookDetailIds): array
    {
        if ($bookDetailIds === []) {
            return [];
        }

        return $this
            ->model
            ->newQuery()
            ->where('user_id', $userId)
            ->whereIn('book_detail_id', $bookDetailIds)
            ->pluck('book_detail_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function create(array $attributes): BookFavourite
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function restore(BookFavourite $favourite): BookFavourite
    {
        $favourite->restore();

        return $favourite->refresh();
    }

    public function delete(BookFavourite $favourite): void
    {
        $favourite->delete();
    }
}
