<?php

namespace Modules\Authentication\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\Concerns\FindsUsersByCredential;

class UserRepository
{
    use FindsUsersByCredential;

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->with(['profile', 'userRoles.role', 'userRoles.revokedRole'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(fn ($builder) => $builder
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('profile', fn ($profile) => $profile->where('fullname', 'like', "%{$search}%")));
            })
            ->when(isset($filters['is_blocked']), fn ($query) => $query->where('is_blocked', $filters['is_blocked']))
            ->when(filled($filters['role_id'] ?? null), fn ($query) => $query->whereHas(
                'activeUserRoles',
                fn ($roles) => $roles->where('role_id', $filters['role_id']),
            ))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): ?User
    {
        return User::query()
            ->with(['profile', 'userRoles.role', 'userRoles.assignedBy.profile', 'userRoles.revokedRole'])
            ->find($id);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
