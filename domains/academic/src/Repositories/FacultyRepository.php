<?php

namespace Modules\Academic\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Models\Faculty;

class FacultyRepository
{
    public function __construct(protected Faculty $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->withCount('departments')
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

    public function findById(int $id): ?Faculty
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Faculty
    {
        return $this->model->newQuery()
            ->withCount('departments')
            ->find($id);
    }

    public function create(array $attributes): Faculty
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Faculty $faculty, array $attributes): Faculty
    {
        $faculty->update($attributes);

        return $faculty->refresh();
    }

    public function delete(Faculty $faculty): bool
    {
        return (bool) $faculty->delete();
    }

    public function countDepartments(Faculty $faculty): int
    {
        return $faculty->departments()->count();
    }
}
