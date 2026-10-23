<?php

namespace Modules\Academic\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Models\Department;
use Modules\Academic\Repositories\DepartmentRepository;

class DepartmentService
{
    public function __construct(protected DepartmentRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Department
    {
        $department = $this->repository->findByIdWithRelations($id);

        if (! $department) {
            throw AcademicException::notFound('Department');
        }

        return $department;
    }

    public function store(array $data): Department
    {
        return $this->repository->create(collect($data)->only([
            'faculty_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function update(int $id, array $data): Department
    {
        $department = $this->findOrFail($id);

        return $this->repository->update($department, collect($data)->only([
            'faculty_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $department = $this->findOrFail($id);

        if ($this->repository->countProgrammes($department) > 0) {
            throw AcademicException::unprocessable(
                'Cannot delete a department that has programmes.',
            );
        }

        $this->repository->delete($department);
    }

    protected function findOrFail(int $id): Department
    {
        $department = $this->repository->findById($id);

        if (! $department) {
            throw AcademicException::notFound('Department');
        }

        return $department;
    }
}
