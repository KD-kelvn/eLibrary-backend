<?php

namespace Modules\Authorization\Services\Permissions;

use App\Support\AdminActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Exceptions\AuthorizationException;
use Modules\Authorization\Models\SystemAction;
use Modules\Authorization\Models\SystemActionRole;
use Modules\Authorization\Models\UserSystemAction;
use Modules\Authorization\Repositories\Permissions\SystemActionRepository;

class SystemActionService
{
    public function __construct(
        private readonly SystemActionRepository $repository
    ) {}

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
        return DB::transaction(function () use ($data) {
            $action = $this->repository->create($data);
            $this->syncOwningRolePivot($action, (int) $action->role_id);

            AdminActivity::log('System action created', $action, [
                'code' => $action->code,
                'role_id' => $action->role_id,
            ], event: 'created');

            return $this->show($action->id);
        });
    }

    public function update(int $id, array $data): SystemAction
    {
        return DB::transaction(function () use ($id, $data) {
            $action = $this->find($id);
            $previousRoleId = (int) $action->role_id;
            $nextRoleId = array_key_exists('role_id', $data)
                ? (int) $data['role_id']
                : $previousRoleId;

            if ($nextRoleId !== $previousRoleId) {
                $this->assertCanReassignRole($action);
            }

            $action = $this->repository->update($action, $data);

            if ($nextRoleId !== $previousRoleId) {
                $this->syncOwningRolePivot($action, $nextRoleId);
            }

            AdminActivity::log(
                'System action updated',
                $action,
                [
                    'before' => ['role_id' => $previousRoleId, 'keys' => array_keys($data)],
                    'after' => [
                        'role_id' => $action->role_id,
                        'code' => $action->code,
                        'is_active' => $action->is_active,
                    ],
                ],
                logName: 'authorization',
                event: 'permission.changed',
            );

            return $this->show($action->id);
        });
    }

    public function destroy(int $id): void
    {
        $action = $this->find($id);

        if ($this->hasUserGrants($action)) {
            throw AuthorizationException::unprocessable(
                'Cannot delete this action while it is assigned to one or more users. Revoke those assignments first.',
            );
        }

        AdminActivity::log('System action deleted', $action, ['code' => $action->code], event: 'deleted');
        $this->repository->delete($action);
    }

    public function assignRole(array $data): SystemActionRole
    {
        $action = $this->find((int) $data['system_action_id']);
        $roleId = (int) $data['role_id'];

        if ((int) $action->role_id === $roleId) {
            return SystemActionRole::query()
                ->where('system_action_id', $action->id)
                ->where('role_id', $roleId)
                ->first()
                    ?? SystemActionRole::query()->create([
                        'system_action_id' => $action->id,
                        'role_id' => $roleId,
                    ]);
        }

        $this->assertCanReassignRole($action);

        return DB::transaction(function () use ($action, $roleId) {
            $action->update(['role_id' => $roleId]);
            $this->syncOwningRolePivot($action->fresh(), $roleId);

            return SystemActionRole::query()
                ->where('system_action_id', $action->id)
                ->where('role_id', $roleId)
                ->firstOrFail();
        });
    }

    public function removeRole(int $id): void
    {
        SystemActionRole::query()->find($id)
            ?? throw AuthorizationException::notFound('System action role assignment');

        throw AuthorizationException::unprocessable(
            'Actions must belong to a role. Reassign the action to another role instead of removing it.',
        );
    }

    private function syncOwningRolePivot(SystemAction $action, int $roleId): void
    {
        SystemActionRole::query()
            ->where('system_action_id', $action->id)
            ->where('role_id', '!=', $roleId)
            ->get()
            ->each(fn (SystemActionRole $row) => $row->delete());

        SystemActionRole::query()->firstOrCreate([
            'system_action_id' => $action->id,
            'role_id' => $roleId,
        ]);
    }

    private function assertCanReassignRole(SystemAction $action): void
    {
        if ($this->hasUserGrants($action)) {
            throw AuthorizationException::unprocessable(
                'Cannot move this action to another role while it is assigned to users. Revoke those user action assignments first.',
            );
        }
    }

    private function hasUserGrants(SystemAction $action): bool
    {
        return UserSystemAction::query()
            ->where('system_action_id', $action->id)
            ->exists();
    }

    private function find(int $id): SystemAction
    {
        return $this->repository->find($id)
            ?? throw AuthorizationException::notFound('System action');
    }
}
