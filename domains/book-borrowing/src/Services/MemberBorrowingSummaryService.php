<?php

namespace Modules\BookBorrowing\Services;

use Modules\BookBorrowing\Http\Resources\MemberBorrowingSummaryResource;
use Modules\BookBorrowing\Http\Resources\MemberPenaltySummaryResource;
use Modules\BookBorrowing\Repositories\BorrowingRequestRepository;
use Modules\BookBorrowing\Repositories\PenaltyBatchRepository;

class MemberBorrowingSummaryService
{
    public function __construct(
        protected BorrowingRequestRepository $requestRepository,
        protected PenaltyBatchRepository $penaltyBatchRepository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function activeBorrowings(int $userId, int $limit = 5): array
    {
        return MemberBorrowingSummaryResource::collection(
            $this->requestRepository->activeForUser($userId, $limit),
        )->resolve();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activePenalties(int $userId, int $limit = 5): array
    {
        return MemberPenaltySummaryResource::collection(
            $this->penaltyBatchRepository->pendingForUser($userId, $limit),
        )->resolve();
    }
}
