<?php

namespace Modules\Academic\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Models\Programme;

class ProgrammeRepository
{
    public function __construct(protected Programme $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('department')
            ->withCount('courses')
            ->when(
                filled($filters['department_id'] ?? null),
                fn ($query) => $query->where('department_id', $filters['department_id']),
            )
            ->when(
                filled($filters['search'] ?? null),
                fn ($query) => $query->where(function ($builder) use ($filters) {
                    $search = $filters['search'];
                    $builder->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_sw', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Programme
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Programme
    {
        return $this->model->newQuery()
            ->with('department')
            ->withCount('courses')
            ->find($id);
    }

    public function create(array $attributes): Programme
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Programme $programme, array $attributes): Programme
    {
        $programme->update($attributes);

        return $programme->refresh();
    }

    public function delete(Programme $programme): bool
    {
        return (bool) $programme->delete();
    }

    public function countCourses(Programme $programme): int
    {
        return $programme->courses()->count();
    }
}
