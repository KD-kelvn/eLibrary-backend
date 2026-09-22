<?php

namespace Modules\BookCatalogue\Services;

use Illuminate\Support\Collection;
use Modules\BookCatalogue\Exceptions\BookCatalogueException;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\BookFavourite;
use Modules\BookCatalogue\Repositories\BookDetailRepository;
use Modules\BookCatalogue\Repositories\BookFavouriteRepository;

class BookFavouriteService
{
    public function __construct(
        protected BookFavouriteRepository $favourites,
        protected BookDetailRepository $books,
    ) {}

    /**
     * @return Collection<int, BookDetail>
     */
    public function listForUser(int $userId, int $limit = 12): Collection
    {
        return $this
            ->favourites
            ->listForUser($userId, $limit)
            ->map(function (BookFavourite $favourite) {
                $book = $favourite->bookDetail;
                if ($book) {
                    $book->setAttribute('is_favourite', true);
                }

                return $book;
            })
            ->filter();
    }

    public function store(int $userId, int $bookDetailId): BookFavourite
    {
        $book = $this->books->findById($bookDetailId);
        if (! $book) {
            throw BookCatalogueException::notFound('Book');
        }

        $existing = $this->favourites->findForUserBook($userId, $bookDetailId);
        if ($existing) {
            return $existing->load([
                'bookDetail.categories',
                'bookDetail.tags',
                'bookDetail.digitalBook',
            ]);
        }

        $trashed = $this->favourites->findTrashedForUserBook($userId, $bookDetailId);
        if ($trashed) {
            return $this->favourites->restore($trashed)->load([
                'bookDetail.categories',
                'bookDetail.tags',
                'bookDetail.digitalBook',
            ]);
        }

        return $this->favourites->create([
            'user_id' => $userId,
            'book_detail_id' => $bookDetailId,
        ])->load([
            'bookDetail.categories',
            'bookDetail.tags',
            'bookDetail.digitalBook',
        ]);
    }

    public function destroyForUserBook(int $userId, int $bookDetailId): void
    {
        $favourite = $this->favourites->findForUserBook($userId, $bookDetailId);
        if (! $favourite) {
            return;
        }

        $this->favourites->delete($favourite);
    }

    public function isFavourite(int $userId, int $bookDetailId): bool
    {
        return $this->favourites->existsForUserBook($userId, $bookDetailId);
    }

    /**
     * @return list<int>
     */
    public function favouriteIdsForUser(int $userId): array
    {
        return $this->favourites->allBookIdsForUser($userId);
    }
}
