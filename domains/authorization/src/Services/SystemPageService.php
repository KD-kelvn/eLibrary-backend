<?php

namespace Modules\Authorization\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemPage;
use Modules\Authorization\Repositories\SystemPageRepository;

class SystemPageService
{
    public function __construct(protected SystemPageRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): SystemPage
    {
        $page = $this->repository->findByIdWithRelations($id);

        if (! $page) {
            throw AuthorizationException::notFound('System page');
        }

        return $page;
    }

    public function store(array $data): SystemPage
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
            'url',
            'is_public',
        ])->all());
    }

    public function update(int $id, array $data): SystemPage
    {
        $page = $this->findOrFail($id);

        return $this->repository->update($page, collect($data)->only([
            'name',
            'description',
            'code',
            'url',
            'is_public',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $page = $this->findOrFail($id);
        $this->repository->delete($page);
    }

    protected function findOrFail(int $id): SystemPage
    {
        $page = $this->repository->findById($id);

        if (! $page) {
            throw AuthorizationException::notFound('System page');
        }

        return $page;
    }
}
