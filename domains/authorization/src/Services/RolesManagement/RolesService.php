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

            AdminActivity::log('Role deleted', $role, [
                'code' => $role->code,
                'deleted_actions' => $actions->pluck('code')->all(),
            ], event: 'deleted');

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
