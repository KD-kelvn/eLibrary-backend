<?php

namespace Modules\Academic\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Repositories\FacultyRepository;

class FacultyService
{
    public function __construct(protected FacultyRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Faculty
    {
        $faculty = $this->repository->findByIdWithRelations($id);

        if (! $faculty) {
            throw AcademicException::notFound('Faculty');
        }

        return $faculty;
    }

    public function store(array $data): Faculty
    {
        return $this->repository->create(collect($data)->only([
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function update(int $id, array $data): Faculty
    {
        $faculty = $this->findOrFail($id);

        return $this->repository->update($faculty, collect($data)->only([
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $faculty = $this->findOrFail($id);

        if ($this->repository->countDepartments($faculty) > 0) {
            throw AcademicException::unprocessable(
                'Cannot delete a faculty that has departments.',
            );
        }

        $this->repository->delete($faculty);
    }

    protected function findOrFail(int $id): Faculty
    {
        $faculty = $this->repository->findById($id);

        if (! $faculty) {
            throw AcademicException::notFound('Faculty');
        }

        return $faculty;
    }
}
