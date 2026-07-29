<?php

namespace Modules\BookBorrowing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class PenaltyPolicyRepository
{
    public function __construct(protected PenaltyPolicy $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
                }),
            )
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?PenaltyPolicy
    {
        return $this->model->newQuery()->find($id);
    }

    public function findActive(): ?PenaltyPolicy
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->latest('effective_from')
            ->latest('id')
            ->first();
    }

    public function create(array $attributes): PenaltyPolicy
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(PenaltyPolicy $policy, array $attributes): PenaltyPolicy
    {
        $policy->update($attributes);

        return $policy->refresh();
    }

    public function delete(PenaltyPolicy $policy): bool
    {
        return (bool) $policy->delete();
    }

    public function deactivateOthers(?int $exceptId = null): void
    {
        $this->model->newQuery()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
