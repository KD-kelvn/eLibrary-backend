<?php

namespace Modules\Authorization\Services\RolesManagement;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\UserSystemAction;
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
            'is_system' => false,
        ]);

        AdminActivity::log(
            'Role created',
            $role,
            ['code' => $role->code, 'before' => null, 'after' => $role->only(['name', 'code', 'status', 'is_system'])],
            logName: 'authorization',
            event: 'role.created',
        );

        return $role;
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->findOrFail($id);
        $before = $role->only(['name', 'description', 'status', 'code', 'is_system']);

        if ($role->is_system) {
            if (array_key_exists('name', $data) && $data['name'] !== $role->name) {
                throw AuthorizationException::unprocessable(
                    'System roles cannot be renamed.',
                    'SYSTEM_ROLE',
                );
            }

            if (array_key_exists('code', $data) && $data['code'] !== $role->code) {
                throw AuthorizationException::unprocessable(
                    'System role codes cannot be changed.',
                    'SYSTEM_ROLE',
                );
            }
        }

        $role = $this->repository->update($role, collect($data)->only([
            'name',
            'description',
            'status',
            'code',
        ])->all());

        AdminActivity::log(
            'Role updated',
            $role,
            [
                'before' => $before,
                'after' => $role->only(['name', 'description', 'status', 'code', 'is_system']),
            ],
            logName: 'authorization',
            event: 'role.updated',
        );

        return $role;
    }

    public function destroy(int $id): void
    {
        $role = $this->findOrFail($id);

        if ($role->is_system) {
            throw AuthorizationException::unprocessable(
                'System roles cannot be deleted.',
                'SYSTEM_ROLE',
            );
        }

        $assignmentCount = $role->userRoles()->count();
        if ($assignmentCount > 0) {
            throw AuthorizationException::unprocessable(
                'Cannot delete a role that is assigned to users. Revoke or delete those assignments first.',
            );
        }

        DB::transaction(function () use ($role) {
            $actions = SystemAction::query()->where('role_id', $role->id)->get();

            foreach ($actions as $action) {
                UserSystemAction::query()
                    ->where('system_action_id', $action->id)
                    ->get()
                    ->each(fn (UserSystemAction $grant) => $grant->delete());

                $action->systemActionRoles()->get()->each(
                    fn ($pivot) => $pivot->delete(),
                );
                $action->delete();
            }

            AdminActivity::log(
                'Role deleted',
                $role,
                [
                    'before' => $role->only(['name', 'code', 'status']),
                    'after' => null,
                    'deleted_actions' => $actions->pluck('code')->all(),
                ],
                logName: 'authorization',
                event: 'role.deleted',
            );

            $this->repository->delete($role);
        });
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
