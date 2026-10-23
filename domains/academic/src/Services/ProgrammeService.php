<?php

namespace Modules\Academic\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Academic\Exceptions\AcademicException;
use Modules\Academic\Models\Programme;
use Modules\Academic\Repositories\ProgrammeRepository;

class ProgrammeService
{
    public function __construct(protected ProgrammeRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Programme
    {
        $programme = $this->repository->findByIdWithRelations($id);

        if (! $programme) {
            throw AcademicException::notFound('Programme');
        }

        return $programme;
    }

    public function store(array $data): Programme
    {
        return $this->repository->create(collect($data)->only([
            'department_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function update(int $id, array $data): Programme
    {
        $programme = $this->findOrFail($id);

        return $this->repository->update($programme, collect($data)->only([
            'department_id',
            'code',
            'name_en',
            'name_sw',
            'is_active',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $programme = $this->findOrFail($id);

        if ($this->repository->countCourses($programme) > 0) {
            throw AcademicException::unprocessable(
                'Cannot delete a programme that has courses.',
            );
        }

        $this->repository->delete($programme);
    }

    protected function findOrFail(int $id): Programme
    {
        $programme = $this->repository->findById($id);

        if (! $programme) {
            throw AcademicException::notFound('Programme');
        }

        return $programme;
    }
}
