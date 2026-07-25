<?php

namespace Modules\Authentication\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use App\Support\AdminActivity;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;

class UserManagementService
{
    private const CREATABLE_ROLE_CODES = ['librarian', 'view_only'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProfileRepository $profiles,
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->users->paginate($filters, $perPage);
    }

    public function show(int $id): User
    {
        return $this->users->findWithRelations($id)
            ?? throw AuthenticationException::userNotFound();
    }

    public function store(array $data, int $assignedBy): User
    {
        $role = Role::query()
            ->whereKey($data['role_id'])
            ->whereIn('code', self::CREATABLE_ROLE_CODES)
            ->where('status', RoleStatusEnum::Active->value)
            ->first();

        if (! $role) {
            throw new AuthenticationException(
                'Admins may only create users with an active librarian or view-only role.',
                422,
            );
        }

        $this->assertUniqueCredentials($data);

        return DB::transaction(function () use ($data, $assignedBy, $role) {
            $user = $this->users->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_blocked' => $data['is_blocked'] ?? false,
            ]);

            $this->profiles->createForUser($user->id, [
                'fullname' => $data['fullname'],
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
            ]);

            UserRole::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $created = $this->show($user->id);

            AdminActivity::log(
                'System user created',
                $created,
                [
                    'role_id' => $role->id,
                    'role_code' => $role->code,
                ],
                event: 'created',
            );

            return $created;
        });
    }

    public function update(int $id, array $data): User
    {
        $user = $this->show($id);

        return DB::transaction(function () use ($user, $data) {
            $userData = collect($data)->only([
                'username',
                'email',
                'phone',
                'password',
                'is_blocked',
            ])->all();

            $profileData = collect($data)->only([
                'fullname',
                'gender',
                'dob',
            ])->all();

            if ($userData !== []) {
                $this->users->update($user, $userData);
            }

            if ($profileData !== []) {
                $this->profiles->updateOrCreateForUser($user->id, $profileData);
            }

            if (($data['is_blocked'] ?? false) === true) {
                $user->tokens()->delete();
            }

            $updated = $this->show($user->id);

            AdminActivity::log(
                'System user updated',
                $updated,
                ['changed' => array_keys($data)],
                event: 'updated',
            );

            return $updated;
        });
    }

    public function setBlocked(int $id, bool $isBlocked): User
    {
        $user = $this->show($id);
        $this->users->update($user, ['is_blocked' => $isBlocked]);

        if ($isBlocked) {
            $user->tokens()->delete();
        }

        $updated = $this->show($user->id);

        AdminActivity::log(
            $isBlocked ? 'User blocked from system usage' : 'User unblocked',
            $updated,
            ['is_blocked' => $isBlocked],
            event: $isBlocked ? 'blocked' : 'unblocked',
        );

        return $updated;
    }

    public function destroy(int $id): void
    {
        $user = $this->show($id);
        $user->tokens()->delete();

        AdminActivity::log(
            'System user deleted',
            $user,
            ['username' => $user->username, 'email' => $user->email],
            event: 'deleted',
        );

        $this->users->delete($user);
    }

    private function assertUniqueCredentials(array $data): void
    {
        if ($this->users->existsByUsername($data['username'])) {
            throw AuthenticationException::duplicateAttribute('username');
        }

        if ($this->users->existsByEmail($data['email'])) {
            throw AuthenticationException::duplicateAttribute('email');
        }

        if (! empty($data['phone']) && $this->users->existsByPhone($data['phone'])) {
            throw AuthenticationException::duplicateAttribute('phone');
        }
    }
}
