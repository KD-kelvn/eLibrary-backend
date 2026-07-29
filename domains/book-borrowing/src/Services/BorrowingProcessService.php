<?php

namespace Modules\BookBorrowing\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\BookBorrowing\Exceptions\BookBorrowingException;
use Modules\BookBorrowing\Models\BorrowingProcess;
use Modules\BookBorrowing\Repositories\BorrowingProcessRepository;

class BorrowingProcessService
{
    public function __construct(protected BorrowingProcessRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function options(): Collection
    {
        return $this->repository->allOrdered();
    }

    public function show(int $id): BorrowingProcess
    {
        $process = $this->repository->findById($id);

        if (! $process) {
            throw BookBorrowingException::notFound('Borrowing process');
        }

        return $process;
    }

    public function store(array $data): BorrowingProcess
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'index_no',
            'status_name',
            'status_color',
            'status_code',
            'sender_role_id',
            'receiver_role_id',
            'is_final',
        ])->all());
    }

    public function update(int $id, array $data): BorrowingProcess
    {
        $process = $this->findOrFail($id);

        // status_code is immutable after create — changing it breaks progress history.
        $attributes = collect($data)->only([
            'name',
            'description',
            'index_no',
            'status_name',
            'status_color',
            'sender_role_id',
            'receiver_role_id',
            'is_final',
        ])->all();

        return $this->repository->update($process, $attributes);
    }

    public function destroy(int $id): void
    {
        $process = $this->findOrFail($id);

        if ($this->repository->countProgresses($process) > 0) {
            throw BookBorrowingException::unprocessable(
                'Cannot delete a process that is used by borrowing progress records.',
            );
        }

        $this->repository->delete($process);
    }

    public function findByCode(string $statusCode): BorrowingProcess
    {
        $process = $this->repository->findByCode($statusCode);

        if (! $process) {
            throw BookBorrowingException::notFound("Borrowing process [{$statusCode}]");
        }

        return $process;
    }

    protected function findOrFail(int $id): BorrowingProcess
    {
        $process = $this->repository->findById($id);

        if (! $process) {
            throw BookBorrowingException::notFound('Borrowing process');
        }

        return $process;
    }
}
