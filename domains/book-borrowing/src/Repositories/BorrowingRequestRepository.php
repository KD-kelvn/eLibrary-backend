<?php

namespace Modules\BookBorrowing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;
use Modules\BookBorrowing\Models\BorrowingRequest;

class BorrowingRequestRepository
{
    public function __construct(protected BorrowingRequest $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?BorrowingRequest
    {
        return $this->model->newQuery()
            ->with($this->defaultRelations())
            ->find($id);
    }

    public function create(array $attributes): BorrowingRequest
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(BorrowingRequest $request, array $attributes): BorrowingRequest
    {
        $request->update($attributes);

        return $request->refresh();
    }

    public function delete(BorrowingRequest $request): bool
    {
        return (bool) $request->delete();
    }

    /**
     * Issued borrows past overdue threshold with no open PENDING penalty batch.
     *
     * @return \Illuminate\Support\Collection<int, BorrowingRequest>
     */
    public function overdueIssuedWithoutPendingBatch(\DateTimeInterface $overdueBefore): \Illuminate\Support\Collection
    {
        return $this->model->newQuery()
            ->with(['latestProgress', 'penaltyBatches'])
            ->whereNotNull('end_date')
            ->where('end_date', '<', $overdueBefore)
            ->whereHas('latestProgress', function (Builder $query) {
                $query->where('status_code', BorrowingProcessCodeEnum::Approved->value);
            })
            ->whereDoesntHave('penaltyBatches', function (Builder $query) {
                $query->where('status', 'PENDING');
            })
            ->get();
    }

    public function countByLatestStatus(string $statusCode): int
    {
        return $this->model->newQuery()
            ->whereHas('latestProgress', fn (Builder $query) => $query->where('status_code', $statusCode))
            ->count();
    }

    public function countExpiringSoon(int $withinDays = 3): int
    {
        return $this->model->newQuery()
            ->whereHas('latestProgress', fn (Builder $query) => $query->where(
                'status_code',
                BorrowingProcessCodeEnum::Approved->value,
            ))
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($withinDays)])
            ->count();
    }

    protected function baseQuery(array $filters): Builder
    {
        $tab = $filters['tab'] ?? 'all';
        $statusCode = $filters['status_code'] ?? null;

        return $this->model->newQuery()
            ->with($this->defaultRelations())
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];
                    $query->where(function (Builder $builder) use ($search) {
                        $builder->whereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })->orWhereHas('book', function (Builder $bookQuery) use ($search) {
                            $bookQuery->where('title', 'like', "%{$search}%")
                                ->orWhere('isbn', 'like', "%{$search}%");
                        });
                    });
                },
            )
            ->when($tab === 'unattended', function (Builder $query) {
                $query->whereHas('latestProgress', fn (Builder $q) => $q->where(
                    'status_code',
                    BorrowingProcessCodeEnum::Created->value,
                ));
            })
            ->when($tab === 'issued', function (Builder $query) {
                $query->whereHas('latestProgress', fn (Builder $q) => $q->where(
                    'status_code',
                    BorrowingProcessCodeEnum::Approved->value,
                ));
            })
            ->when(
                $tab === 'all' && filled($statusCode),
                fn (Builder $query) => $query->whereHas(
                    'latestProgress',
                    fn (Builder $q) => $q->where('status_code', $statusCode),
                ),
            );
    }

    /** @return list<string|\Closure> */
    protected function defaultRelations(): array
    {
        return [
            'user',
            'book',
            'bookType',
            'latestProgress.process',
            'latestProgress.senderRole',
            'latestProgress.receiverRole',
            'extensions',
            'penaltyBatches.penalties',
        ];
    }
}
