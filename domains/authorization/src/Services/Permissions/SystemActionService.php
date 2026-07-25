<?php

namespace Modules\Authorization\Services\Permissions;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;
use Modules\Authorization\Repositories\Permissions\SystemActionRepository;

class SystemActionService
{
    public function __construct(private readonly SystemActionRepository $repository) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): SystemAction
    {
        return $this->repository->find($id, true)
            ?? throw AuthorizationException::notFound('System action');
    }

    public function store(array $data): SystemAction
    {
        $action = $this->repository->create($data);
        AdminActivity::log('System action created', $action, ['code' => $action->code], event: 'created');

        return $action;
    }

    public function update(int $id, array $data): SystemAction
    {
        $action = $this->repository->update($this->find($id), $data);
        AdminActivity::log('System action updated', $action, ['changed' => array_keys($data)], event: 'updated');

        return $action;
    }

    public function destroy(int $id): void
    {
        $action = $this->find($id);
        AdminActivity::log('System action deleted', $action, ['code' => $action->code], event: 'deleted');
        $this->repository->delete($action);
    }

    public function assignRole(array $data): SystemActionRole
    {
        if (SystemActionRole::query()->where($data)->exists()) {
            throw AuthorizationException::unprocessable('The role is already assigned to this action.');
        }

        return SystemActionRole::query()->create($data);
    }

    public function removeRole(int $id): void
    {
        $assignment = SystemActionRole::query()->find($id)
            ?? throw AuthorizationException::notFound('System action role assignment');
        $assignment->delete();
    }

    private function find(int $id): SystemAction
    {
        return $this->repository->find($id)
            ?? throw AuthorizationException::notFound('System action');
    }
}
