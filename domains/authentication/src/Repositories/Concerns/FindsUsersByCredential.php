<?php

namespace Modules\Authentication\Repositories\Concerns;

use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Models\User;
use Modules\Authentication\Support\CredentialResolver;
use Modules\Authentication\Support\PhoneNumberNormalizer;

trait FindsUsersByCredential
{
    public function findByCredential(string $identifier, CredentialType $type): ?User
    {
        $column = CredentialResolver::columnFor($type);
        $value = $identifier;

        if ($type === CredentialType::Phone) {
            $value = app(PhoneNumberNormalizer::class)->normalize($identifier) ?? $identifier;
        }

        if ($type === CredentialType::Email) {
            return User::query()
                ->whereRaw('lower(email) = ?', [strtolower($value)])
                ->first();
        }

        if ($type === CredentialType::Username) {
            return User::query()
                ->whereRaw('lower(username) = lower(?)', [$value])
                ->first();
        }

        return User::query()->where($column, $value)->first();
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
        $normalized = app(PhoneNumberNormalizer::class)->normalize($phone) ?? $phone;

        return $this->attributeExists('phone', $normalized, $exceptUserId);
    }

    protected function attributeExists(string $column, string $value, ?int $exceptUserId = null): bool
    {
        $query = User::query();

        if (in_array($column, ['username', 'email'], true)) {
            $query->whereRaw("lower({$column}) = lower(?)", [$value]);
        } else {
            $query->where($column, $value);
        }

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        return $query->exists();
    }
}
