<?php

namespace Modules\Authorization\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemModule;
use Modules\Authorization\Repositories\SystemModuleRepository;

class SystemModuleService
{
    public function __construct(protected SystemModuleRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): SystemModule
    {
        $module = $this->repository->findByIdWithRelations($id);

        if (! $module) {
            throw AuthorizationException::notFound('System module');
        }

        return $module;
    }

    public function store(array $data): SystemModule
    {
        return $this->repository->create(collect($data)->only([
            'name',
            'description',
            'code',
            'icon_code',
            'bg_color',
            'landing_url',
        ])->all());
    }

    public function update(int $id, array $data): SystemModule
    {
        $module = $this->findOrFail($id);

        return $this->repository->update($module, collect($data)->only([
            'name',
            'description',
            'code',
            'icon_code',
            'bg_color',
            'landing_url',
        ])->all());
    }

    public function destroy(int $id): void
    {
        $module = $this->findOrFail($id);
        $this->repository->delete($module);
    }

    protected function findOrFail(int $id): SystemModule
    {
        $module = $this->repository->findById($id);

        if (! $module) {
            throw AuthorizationException::notFound('System module');
        }

        return $module;
    }
}
