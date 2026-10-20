<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use App\Support\ApiErrorCode;
use Modules\BookBorrowing\Models\BorrowingRequest;
use Modules\BookBorrowing\Repositories\BorrowingRequestRepository;
use Modules\BookCatalogue\Enums\BookTypeEnum;
use Modules\BookCatalogue\Enums\PhysicalBookCopyStatusEnum;
use Modules\BookCatalogue\Models\BookDetail;
use Modules\BookCatalogue\Models\PhysicalBookCopy;

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

    public function storeForUser(int $userId, array $data): BorrowingRequest
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = User::query()->lockForUpdate()->find($userId);

            if (! $user) {
                throw BookBorrowingException::notFound('User');
            }

            $book = BookDetail::query()->find($data['book_id'] ?? null);

            if (! $book) {
                throw BookBorrowingException::notFound('Book');
            }

            if ($book->type_code !== BookTypeEnum::Physical) {
                throw BookBorrowingException::unprocessable(
                    'Only physical books can be requested for borrowing.',
                    ApiErrorCode::NOT_PHYSICAL_BOOK,
                );
            }

            $physicalBooks = $book->physicalBooks()->lockForUpdate()->get();
            $availableCopies = PhysicalBookCopy::query()
                ->whereIn('physical_book_id', $physicalBooks->modelKeys())
                ->where('status', PhysicalBookCopyStatusEnum::Available)
                ->lockForUpdate()
                ->get(['id'])
                ->count();

            if ($availableCopies < 1) {
                throw BookBorrowingException::unprocessable(
                    'This book is currently unavailable for borrowing.',
                    ApiErrorCode::BOOK_UNAVAILABLE,
                );
            }

            $openRequestExists = BorrowingRequest::query()
                ->where('user_id', $userId)
                ->where('book_id', $book->id)
                ->whereHas('latestProgress', function ($query) {
                    $query->whereIn('status_code', [
                        BorrowingProcessCodeEnum::Created->value,
                        BorrowingProcessCodeEnum::Approved->value,
                    ]);
                })
                ->exists();

            if ($openRequestExists) {
                throw BookBorrowingException::unprocessable(
                    'You already have an active borrowing request for this book.',
                    ApiErrorCode::DUPLICATE_REQUEST,
                );
            }

            $issuedCount = BorrowingRequest::query()
                ->where('user_id', $userId)
                ->whereHas('latestProgress', fn ($query) => $query->whereIn('status_code', [
                    BorrowingProcessCodeEnum::Created->value,
                    BorrowingProcessCodeEnum::Approved->value,
                ]))
                ->count();

            if ($issuedCount >= 2) {
                throw BookBorrowingException::unprocessable(
                    'You have reached the maximum of 2 active borrowing requests or loans.',
                    ApiErrorCode::LOAN_LIMIT,
                );
            }

            $hasUnpaidPenalties = BorrowingRequest::query()
                ->where('user_id', $userId)
                ->whereHas('penaltyBatches', function ($query) {
                    $query
                        ->where('status', PenaltyBatchStatusEnum::Pending)
                        ->whereHas('penalties', fn ($penaltyQuery) => $penaltyQuery->where('is_paid', false));
                })
                ->exists();

            if ($hasUnpaidPenalties) {
                throw BookBorrowingException::unprocessable(
                    'Please settle your unpaid fines before requesting another book.',
                    ApiErrorCode::UNPAID_FINES,
                );
            }

            $pendingBookRequests = BorrowingRequest::query()
                ->where('book_id', $book->id)
                ->whereHas('latestProgress', fn ($query) => $query->where(
                    'status_code',
                    BorrowingProcessCodeEnum::Created->value,
                ))
                ->count();

            if ($pendingBookRequests >= $availableCopies) {
                throw BookBorrowingException::unprocessable(
                    'All copies of this book are currently borrowed or reserved.',
                    ApiErrorCode::BOOK_UNAVAILABLE,
                );
            }

            $physicalBook = $physicalBooks->first();
            $payload = [
                'user_id' => $userId,
                'book_id' => $book->id,
                'book_type' => $physicalBook::class,
                'book_type_id' => $physicalBook->id,
            ];

            $request = $this->repository->create($payload);
            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::Created->value);
            $this->progressService->record(
                $request,
                $process,
                $data['remarks'] ?? 'Borrow request submitted by member.',
            );

            return $this->findOrFail($request->id);
        });
    }

    public function approve(int $id, ?string $remarks = null, ?string $startDate = null, ?string $endDate = null): BorrowingRequest
    {
        return DB::transaction(function () use ($id, $remarks, $startDate, $endDate) {
            $request = $this->findOrFail($id);
            $this->assertLatestStatus($request, BorrowingProcessCodeEnum::Created);

            $physicalBookIds = $request->book?->physicalBooks()->lockForUpdate()->pluck('id') ?? collect();
            $copy = PhysicalBookCopy::query()
                ->whereIn('physical_book_id', $physicalBookIds)
                ->where('status', PhysicalBookCopyStatusEnum::Available)
                ->lockForUpdate()
                ->first();

            if (! $copy) {
                throw BookBorrowingException::unprocessable('No available copy remains for this request.');
            }

            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::Approved->value);
            $this->progressService->record($request, $process, $remarks);
            $copy->update(['status' => PhysicalBookCopyStatusEnum::OnLoan]);

            $attributes = [];
            $attributes['physical_book_copy_id'] = $copy->id;
            if ($startDate) {
                $attributes['start_date'] = $startDate;
            } elseif (! $request->start_date) {
                $attributes['start_date'] = now();
            }
            if ($endDate) {
                $attributes['end_date'] = $endDate;
            }

            $this->repository->update($request, $attributes);

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

            $copy = PhysicalBookCopy::query()
                ->lockForUpdate()
                ->find($request->physical_book_copy_id);

            if (! $copy || $copy->status !== PhysicalBookCopyStatusEnum::OnLoan) {
                throw BookBorrowingException::unprocessable('The issued copy could not be located for return.');
            }

            $process = $this->processService->findByCode(BorrowingProcessCodeEnum::BookReturned->value);
            $this->progressService->record($request, $process, $remarks);
            $copy->update(['status' => PhysicalBookCopyStatusEnum::Available]);

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
