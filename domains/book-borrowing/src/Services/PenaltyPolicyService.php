<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Models\PenaltyPolicy;
use Modules\BookBorrowing\Repositories\PenaltyPolicyRepository;

class PenaltyPolicyService
{
    public function __construct(protected PenaltyPolicyRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): PenaltyPolicy
    {
        return $this->findOrFail($id);
    }

    public function store(array $data): PenaltyPolicy
    {
        return DB::transaction(function () use ($data) {
            $attributes = collect($data)->only([
                'name',
                'grace_days',
                'cost_per_day',
                'currency',
                'is_active',
                'effective_from',
                'description',
            ])->all();

            if (! empty($attributes['is_active'])) {
                $this->repository->deactivateOthers();
            }

            return $this->repository->create($attributes);
        });
    }

    public function update(int $id, array $data): PenaltyPolicy
    {
        return DB::transaction(function () use ($id, $data) {
            $policy = $this->findOrFail($id);

            $attributes = collect($data)->only([
                'name',
                'grace_days',
                'cost_per_day',
                'currency',
                'is_active',
                'effective_from',
                'description',
            ])->all();

            if (array_key_exists('is_active', $attributes) && $attributes['is_active']) {
                $this->repository->deactivateOthers($policy->id);
            }

            return $this->repository->update($policy, $attributes);
        });
    }

    public function destroy(int $id): void
    {
        $policy = $this->findOrFail($id);

        if ($policy->is_active) {
            throw BookBorrowingException::unprocessable(
                'Cannot delete the active penalty policy. Activate another policy first.',
            );
        }

        $this->repository->delete($policy);
    }

    public function active(): ?PenaltyPolicy
    {
        return $this->repository->findActive();
    }

    protected function findOrFail(int $id): PenaltyPolicy
    {
        $policy = $this->repository->findById($id);

        if (! $policy) {
            throw BookBorrowingException::notFound('Penalty policy');
        }

        return $policy;
    }
}
