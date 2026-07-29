<?php

namespace Modules\BookBorrowing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\BookBorrowing\Enums\PenaltyBatchStatusEnum;
use Modules\BookBorrowing\Models\PenaltyBatch;

class PenaltyBatchRepository
{
    public function __construct(protected PenaltyBatch $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? null;
        $tab = $filters['tab'] ?? 'all';

        return $this->model->newQuery()
            ->with([
                'borrowingRequest.user',
                'borrowingRequest.book',
                'penalties',
                'stoppedPenalty',
            ])
            ->withCount('penalties')
            ->when(
                filled($filters['search'] ?? null),
                function (Builder $query) use ($filters) {
                    $search = $filters['search'];
                    $query->where(function (Builder $builder) use ($search) {
                        $builder->where('batch_no', 'like', "%{$search}%")
                            ->orWhereHas('borrowingRequest.user', function (Builder $userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->orWhereHas('borrowingRequest.book', function (Builder $bookQuery) use ($search) {
                                $bookQuery->where('title', 'like', "%{$search}%");
                            });
                    });
                },
            )
            ->when(
                filled($tab) && strtolower((string) $tab) !== 'all',
                fn (Builder $query) => $query->where('status', strtoupper((string) $tab)),
            )
            ->when(
                filled($status),
                fn (Builder $query) => $query->where('status', strtoupper((string) $status)),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?PenaltyBatch
    {
        return $this->model->newQuery()
            ->with([
                'borrowingRequest.user',
                'borrowingRequest.book',
                'penalties.payments',
                'stoppedPenalty',
            ])
            ->find($id);
    }

    public function pendingBatches(): Collection
    {
        return $this->model->newQuery()
            ->with('borrowingRequest')
            ->where('status', PenaltyBatchStatusEnum::Pending)
            ->get();
    }

    public function create(array $attributes): PenaltyBatch
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(PenaltyBatch $batch, array $attributes): PenaltyBatch
    {
        $batch->update($attributes);

        return $batch->refresh();
    }

    public function countByStatus(PenaltyBatchStatusEnum $status): int
    {
        return $this->model->newQuery()->where('status', $status)->count();
    }
}
