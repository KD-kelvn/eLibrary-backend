<?php

namespace Modules\Authorization\Services\RolesManagement;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Repositories\RolesManagement\RolesRepository;

class RolesService
{
    public function __construct(protected RolesRepository $repository) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): Role
    {
        $role = $this->repository->findByIdWithRelations($id);

        if (! $role) {
            throw AuthorizationException::notFound('Role');
        }

        return $role;
    }

    public function store(array $data): Role
    {
        $role = $this->repository->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? RoleStatusEnum::Inactive->value,
            'code' => $data['code'],
        ]);

        AdminActivity::log('Role created', $role, ['code' => $role->code], event: 'created');

        return $role;
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->findOrFail($id);

        $role = $this->repository->update($role, collect($data)->only([
            'name',
            'description',
            'status',
            'code',
        ])->all());

        AdminActivity::log(
            'Role updated',
            $role,
            ['changed' => array_keys($data), 'status' => $role->status?->value],
            event: 'updated',
        );

        return $role;
    }

    public function destroy(int $id): void
    {
        $role = $this->findOrFail($id);

        if (
            $this->repository->isActive($role)
            && $this->repository->countActiveAssignments($role) > 0
        ) {
            throw AuthorizationException::forbidden(
                'Cannot delete an active role that has active assignments.',
            );
        }

        AdminActivity::log('Role deleted', $role, ['code' => $role->code], event: 'deleted');
        $this->repository->delete($role);
    }

    protected function findOrFail(int $id): Role
    {
        $role = $this->repository->findById($id);

        if (! $role) {
            throw AuthorizationException::notFound('Role');
        }

        return $role;
    }
}
