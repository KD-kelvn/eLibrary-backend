<?php

namespace Modules\Academic\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Models\Course;

class CourseRepository
{
    public function __construct(protected Course $model) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('programme')
            ->when(
                filled($filters['programme_id'] ?? null),
                fn ($query) => $query->where('programme_id', $filters['programme_id']),
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

    public function findById(int $id): ?Course
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Course
    {
        return $this->model->newQuery()
            ->with('programme')
            ->find($id);
    }

    public function create(array $attributes): Course
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Course $course, array $attributes): Course
    {
        $course->update($attributes);

        return $course->refresh();
    }

    public function delete(Course $course): bool
    {
        return (bool) $course->delete();
    }
}
