<?php

namespace Modules\Academic\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Models\Course;
use Modules\Academic\Repositories\CourseRepository;

class CourseService
{
    public function __construct(protected CourseRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Course
    {
        $course = $this->repository->findByIdWithRelations($id);

        if (! $course) {
            throw AcademicException::notFound('Course');
        }

        return $course;
    }

    public function store(array $data): Course
    {
        return $this->repository->create(collect($data)->only([
            'programme_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function update(int $id, array $data): Course
    {
        $course = $this->findOrFail($id);

        return $this->repository->update($course, collect($data)->only([
            'programme_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $course = $this->findOrFail($id);

        $this->repository->delete($course);
    }

    protected function findOrFail(int $id): Course
    {
        $course = $this->repository->findById($id);

        if (! $course) {
            throw AcademicException::notFound('Course');
        }

        return $course;
    }
}
