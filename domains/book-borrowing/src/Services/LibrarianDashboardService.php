<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Support\Facades\DB;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;
use Modules\BookBorrowing\Repositories\BorrowingRequestRepository;
use Modules\BookBorrowing\Repositories\PenaltyBatchRepository;
use Modules\BookCatalogue\Models\PhysicalBook;
use Modules\BookCatalogue\Models\Shelf;
use Modules\BookReading\Models\ReadingHistory;

class LibrarianDashboardService
{
    public function __construct(
        protected BorrowingRequestRepository $requestRepository,
        protected PenaltyBatchRepository $penaltyBatchRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $shelfStock = Shelf::query()
            ->withSum('physicalBooks as copies_sum', 'copies')
            ->withCount('physicalBooks')
            ->orderBy('name')
            ->get()
            ->map(fn (Shelf $shelf) => [
                'id' => $shelf->id,
                'name' => $shelf->name,
                'code' => $shelf->code,
                'physicalBooksCount' => (int) $shelf->physical_books_count,
                'copies' => (int) ($shelf->copies_sum ?? 0),
            ])
            ->values()
            ->all();

        $totalCopies = (int) PhysicalBook::query()->sum('copies');

        $weeklyReadings = ReadingHistory::query()
            ->where('started_at', '>=', now()->subDays(7))
            ->count();

        return [
            'shelves' => $shelfStock,
            'totalPhysicalCopies' => $totalCopies,
            'weeklyReadings' => $weeklyReadings,
            'unattendedRequests' => $this->requestRepository->countByLatestStatus(
                BorrowingProcessCodeEnum::Created->value,
            ),
            'issuedRequests' => $this->requestRepository->countByLatestStatus(
                BorrowingProcessCodeEnum::Approved->value,
            ),
            'expiringSoon' => $this->requestRepository->countExpiringSoon(3),
            'pendingPenalties' => $this->penaltyBatchRepository->countByStatus(
                PenaltyBatchStatusEnum::Pending,
            ),
            'readingsByDay' => $this->readingsByDay(7),
        ];
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    protected function readingsByDay(int $days): array
    {
        $rows = ReadingHistory::query()
            ->select(DB::raw('DATE(started_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('started_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $series[] = [
                'date' => $date,
                'count' => (int) ($rows[$date]->total ?? 0),
            ];
        }

        return $series;
    }
}
