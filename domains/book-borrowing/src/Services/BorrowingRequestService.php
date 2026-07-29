<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookBorrowing\Repositories\BorrowingRequestRepository;

class BorrowingRequestService
{
    public function __construct(
        protected BorrowingRequestRepository $repository,
        protected BorrowingProcessService $processService,
        protected BorrowingProgressService $progressService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): BorrowingRequest
    {
        return $this->findOrFail($id);
    }

    public function approve(int $id, ?string $remarks = null, ?string $startDate = null, ?string $endDate = null): BorrowingRequest
    {
        return DB::transaction(function () use ($id, $remarks, $startDate, $endDate) {
            $request = $this->findOrFail($id);
            $this->assertLatestStatus($request, BorrowingProcessCodeEnum::Created);

            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::Approved->value);
            $this->progressService->record($request, $process, $remarks);

            $attributes = [];
            if ($startDate) {
                $attributes['start_date'] = $startDate;
            } elseif (! $request->start_date) {
                $attributes['start_date'] = now();
            }
            if ($endDate) {
                $attributes['end_date'] = $endDate;
            }

            if ($attributes !== []) {
                $this->repository->update($request, $attributes);
            }

            return $this->findOrFail($id);
        });
    }

    public function reject(int $id, ?string $remarks = null): BorrowingRequest
    {
        return DB::transaction(function () use ($id, $remarks) {
            $request = $this->findOrFail($id);
            $this->assertLatestStatus($request, BorrowingProcessCodeEnum::Created);

            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::Rejected->value);
            $this->progressService->record($request, $process, $remarks);

            return $this->findOrFail($id);
        });
    }

    public function markReturned(int $id, ?string $remarks = null): BorrowingRequest
    {
        return DB::transaction(function () use ($id, $remarks) {
            $request = $this->findOrFail($id);
            $this->assertLatestStatus($request, BorrowingProcessCodeEnum::Approved);

            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::BookReturned->value);
            $this->progressService->record($request, $process, $remarks);

            return $this->findOrFail($id);
        });
    }

    public function destroy(int $id): void
    {
        $request = $this->findOrFail($id);
        $status = $request->latestProgress?->status_code;

        $deletable = [
            BorrowingProcessCodeEnum::Created->value,
            BorrowingProcessCodeEnum::Rejected->value,
        ];

        if (! in_array($status, $deletable, true)) {
            throw BookBorrowingException::unprocessable(
                'Only unattended or rejected borrowing requests can be deleted.',
            );
        }

        $this->repository->delete($request);
    }

    protected function assertLatestStatus(BorrowingRequest $request, BorrowingProcessCodeEnum $expected): void
    {
        $actual = $request->latestProgress?->status_code;

        if ($actual !== $expected->value) {
            throw BookBorrowingException::unprocessable(
                "Request must be in [{$expected->value}] status. Current: ".($actual ?? 'none').'.',
            );
        }
    }

    protected function findOrFail(int $id): BorrowingRequest
    {
        $request = $this->repository->findById($id);

        if (! $request) {
            throw BookBorrowingException::notFound('Borrowing request');
        }

        return $request;
    }
}
