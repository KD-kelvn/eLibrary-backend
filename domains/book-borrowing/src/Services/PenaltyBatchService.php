<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Models\BorrowingPenalty;
use Modules\BookBorrowing\Models\PenaltyBatch;
use Modules\BookBorrowing\Models\PenaltyPayment;
use Modules\BookBorrowing\Models\StoppedPenalty;
use Modules\BookBorrowing\Repositories\BorrowingRequestRepository;
use Modules\BookBorrowing\Repositories\PenaltyBatchRepository;

class PenaltyBatchService
{
    public function __construct(
        protected PenaltyBatchRepository $repository,
        protected BorrowingRequestRepository $requestRepository,
        protected PenaltyPolicyService $policyService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): PenaltyBatch
    {
        return $this->findOrFail($id);
    }

    /**
     * 00:00 job — create PENDING batches for overdue issued borrows.
     */
    public function createBatchesForOverdue(): int
    {
        $policy = $this->policyService->active();

        if (! $policy) {
            return 0;
        }

        $overdueBefore = now()->subDays($policy->grace_days)->startOfDay();
        $created = 0;

        foreach ($this->requestRepository->overdueIssuedWithoutPendingBatch($overdueBefore) as $request) {
            $this->repository->create([
                'borrowing_request_id' => $request->id,
                'book_type' => $request->book_type,
                'book_type_id' => $request->book_type_id,
                'batch_no' => $this->generateBatchNo(),
                'cost_per_day' => $policy->cost_per_day,
                'status' => PenaltyBatchStatusEnum::Pending,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * 00:10 job — generate today's penalty row for each PENDING batch.
     */
    public function generateDailyPenalties(?\DateTimeInterface $forDate = null): int
    {
        $date = ($forDate ? \Illuminate\Support\Carbon::parse($forDate) : now())->toDateString();
        $created = 0;

        foreach ($this->repository->pendingBatches() as $batch) {
            $exists = BorrowingPenalty::query()
                ->where('penalty_batch_id', $batch->id)
                ->whereDate('penalty_date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            BorrowingPenalty::query()->create([
                'penalty_batch_id' => $batch->id,
                'borrowing_request_id' => $batch->borrowing_request_id,
                'book_type' => $batch->book_type,
                'book_type_id' => $batch->book_type_id,
                'penalty_date' => $date,
                'is_paid' => false,
            ]);
            $created++;
        }

        return $created;
    }

    public function stop(int $id, ?string $reason = null): PenaltyBatch
    {
        return DB::transaction(function () use ($id, $reason) {
            $batch = $this->findOrFail($id);

            if ($batch->status !== PenaltyBatchStatusEnum::Pending) {
                throw BookBorrowingException::unprocessable(
                    'Only pending penalty batches can be stopped.',
                );
            }

            $latestPenalty = $batch->penalties()->latest('penalty_date')->first();

            StoppedPenalty::query()->create([
                'penalty_batch_id' => $batch->id,
                'borrowing_penalty_id' => $latestPenalty?->id,
                'stopped_date' => now(),
                'stopped_reason' => $reason,
            ]);

            $this->repository->update($batch, [
                'status' => PenaltyBatchStatusEnum::Stopped,
            ]);

            return $this->findOrFail($id);
        });
    }

    public function markPaid(int $id, ?string $remarks = null): PenaltyBatch
    {
        return DB::transaction(function () use ($id) {
            $batch = $this->findOrFail($id);

            if ($batch->status === PenaltyBatchStatusEnum::Paid) {
                throw BookBorrowingException::unprocessable('Penalty batch is already paid.');
            }

            if ($batch->status === PenaltyBatchStatusEnum::Stopped) {
                throw BookBorrowingException::unprocessable(
                    'Stopped penalty batches cannot be marked paid. Reopen is not supported yet.',
                );
            }

            $unpaid = $batch->penalties()->where('is_paid', false)->get();

            foreach ($unpaid as $penalty) {
                $penalty->update(['is_paid' => true]);

                PenaltyPayment::query()->create([
                    'borrowing_penalty_id' => $penalty->id,
                    'payment_date' => now(),
                    'payment_amount' => (float) $batch->cost_per_day,
                    'payment_method' => 'manual',
                ]);
            }

            $this->repository->update($batch, [
                'status' => PenaltyBatchStatusEnum::Paid,
            ]);

            return $this->findOrFail($id);
        });
    }

    protected function generateBatchNo(): string
    {
        return 'PB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    protected function findOrFail(int $id): PenaltyBatch
    {
        $batch = $this->repository->findById($id);

        if (! $batch) {
            throw BookBorrowingException::notFound('Penalty batch');
        }

        return $batch;
    }
}
