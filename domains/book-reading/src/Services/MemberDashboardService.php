<?php

namespace Modules\BookReading\Services;

use Modules\BookBorrowing\Services\MemberBorrowingSummaryService;
use Modules\BookCatalogue\Http\Resources\MemberBookCardResource;
use Modules\BookCatalogue\Services\BookFavouriteService;
use Modules\BookReading\Models\ReadingHistory;

class MemberDashboardService
{
    public function __construct(
        protected BookFavouriteService $favourites,
        protected MemberBorrowingSummaryService $borrowings,
    ) {}

    /**
     * @return array{lastRead: array<string, mixed>|null, favourites: mixed, favouriteIds: list<int>, activeBorrowings: list<array<string, mixed>>, activePenalties: list<array<string, mixed>>}
     */
    public function summary(int $userId): array
    {
        $lastHistory = $this->latestReadingForUser($userId);
        $favouriteBooks = $this->favourites->listForUser($userId, 12);
        $favouriteIds = $this->favourites->favouriteIdsForUser($userId);

        $lastRead = null;
        if ($lastHistory?->bookDetail) {
            $book = $lastHistory->bookDetail;
            $isFavourite = in_array((int) $book->id, $favouriteIds, true);
            $book->setAttribute('is_favourite', $isFavourite);

            $lastRead = [
                'historyId' => $lastHistory->id,
                'progressPercent' => (float) $lastHistory->progress_percent,
                'currentLocation' => $lastHistory->current_location,
                'lastReadAt' => $lastHistory->last_read_at,
                'isFavourite' => $isFavourite,
                'book' => MemberBookCardResource::make($book)->resolve(),
            ];
        }

        return [
            'lastRead' => $lastRead,
            'favourites' => MemberBookCardResource::collection($favouriteBooks)->resolve(),
            'favouriteIds' => $favouriteIds,
            'activeBorrowings' => $this->borrowings->activeBorrowings($userId),
            'activePenalties' => $this->borrowings->activePenalties($userId),
        ];
    }

    protected function latestReadingForUser(int $userId): ?ReadingHistory
    {
        $open = ReadingHistory::query()
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->with([
                'bookDetail.categories',
                'bookDetail.tags',
                'bookDetail.digitalBook',
            ])
            ->latest('last_read_at')
            ->first();

        if ($open) {
            return $open;
        }

        return ReadingHistory::query()
            ->where('user_id', $userId)
            ->with([
                'bookDetail.categories',
                'bookDetail.tags',
                'bookDetail.digitalBook',
            ])
            ->latest('last_read_at')
            ->first();
    }
}
