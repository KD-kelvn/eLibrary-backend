<?php

namespace Modules\BookBorrowing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\BookBorrowing\Models\BorrowingProcess;

class BorrowingProcessRepository
{
    public function __construct(protected BorrowingProcess $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['senderRole', 'receiverRole'])
            ->withCount('progresses')
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('status_name', 'like', "%{$search}%")
                        ->orWhere('status_code', 'like', "%{$search}%");
                }),
            )
            ->orderBy('index_no')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function allOrdered(): Collection
    {
        return $this->model->newQuery()
            ->with(['senderRole', 'receiverRole'])
            ->orderBy('index_no')
            ->orderBy('id')
            ->get();
    }

    public function findById(int $id): ?BorrowingProcess
    {
        return $this->model->newQuery()
            ->with(['senderRole', 'receiverRole'])
            ->withCount('progresses')
            ->find($id);
    }

    public function findByCode(string $statusCode): ?BorrowingProcess
    {
        return $this->model->newQuery()
            ->where('status_code', $statusCode)
            ->first();
    }

    public function create(array $attributes): BorrowingProcess
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(BorrowingProcess $process, array $attributes): BorrowingProcess
    {
        $process->update($attributes);

        return $process->fresh(['senderRole', 'receiverRole']);
    }

    public function delete(BorrowingProcess $process): bool
    {
        return (bool) $process->delete();
    }

    public function countProgresses(BorrowingProcess $process): int
    {
        return $process->progresses()->count();
    }
}
