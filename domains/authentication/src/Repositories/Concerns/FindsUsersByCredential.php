<?php

namespace Modules\Authentication\Repositories\Concerns;

use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Models\User;
use Modules\Authentication\Support\CredentialResolver;

trait FindsUsersByCredential
{
    public function findByCredential(string $identifier, CredentialType $type): ?User
    {
        $column = CredentialResolver::columnFor($type);

        return User::query()->where($column, $identifier)->first();
    }

    public function existsByUsername(string $username, ?int $exceptUserId = null): bool
    {
        return $this->attributeExists('username', $username, $exceptUserId);
    }

    public function existsByEmail(string $email, ?int $exceptUserId = null): bool
    {
        return $this->attributeExists('email', $email, $exceptUserId);
    }

    public function existsByPhone(string $phone, ?int $exceptUserId = null): bool
    {
        return $this->attributeExists('phone', $phone, $exceptUserId);
    }

    protected function attributeExists(string $column, string $value, ?int $exceptUserId = null): bool
    {
        $query = User::query()->where($column, $value);

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        return $query->exists();
    }
}
