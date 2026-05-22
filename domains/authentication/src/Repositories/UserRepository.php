<?php

namespace Modules\Authentication\Repositories;

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
}
