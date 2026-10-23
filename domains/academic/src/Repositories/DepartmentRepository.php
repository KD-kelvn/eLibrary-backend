<?php

namespace Modules\Academic\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Models\Department;

class DepartmentRepository
{
    public function __construct(protected Department $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('faculty')
            ->withCount('programmes')
            ->when(
                filled($filters['faculty_id'] ?? null),
                fn ($query) => $query->where('faculty_id', $filters['faculty_id']),
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

    public function findById(int $id): ?Department
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Department
    {
        return $this->model->newQuery()
            ->with('faculty')
            ->withCount('programmes')
            ->find($id);
    }

    public function create(array $attributes): Department
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Department $department, array $attributes): Department
    {
        $department->update($attributes);

        return $department->refresh();
    }

    public function delete(Department $department): bool
    {
        return (bool) $department->delete();
    }

    public function countProgrammes(Department $department): int
    {
        return $department->programmes()->count();
    }
}
