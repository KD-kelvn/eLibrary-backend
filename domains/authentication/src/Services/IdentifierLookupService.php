<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Support\CredentialResolver;
use Modules\Authentication\Support\PhoneNumberNormalizer;

/**
 * Resolve a user by phone, email, or username (FR-AUTH-01).
 * Phone values are normalised to E.164 before lookup.
 */
class IdentifierLookupService
{
    public function __construct(
        protected PhoneNumberNormalizer $phones,
    ) {}

    public function detect(string $identifier): CredentialType
    {
        return CredentialResolver::detect($this->normalizeRaw($identifier));
    }

    public function normalize(string $identifier, ?CredentialType $type = null): string
    {
        $raw = $this->normalizeRaw($identifier);
        $type ??= CredentialResolver::detect($raw);

        return match ($type) {
            CredentialType::Email => strtolower($raw),
            CredentialType::Phone => $this->phones->normalize($raw) ?? $raw,
            CredentialType::Username => $raw,
        };
    }

    public function find(string $identifier): ?User
    {
        $raw = $this->normalizeRaw($identifier);
        if ($raw === '') {
            return null;
        }

        $type = CredentialResolver::detect($raw);
        $value = $this->normalize($raw, $type);

        return match ($type) {
            CredentialType::Email => User::query()
                ->whereRaw('lower(email) = ?', [strtolower($value)])
                ->first(),
            CredentialType::Phone => User::query()
                ->where('phone', $value)
                ->first(),
            CredentialType::Username => $this->findByUsername($value),
        };
    }

    public function findOrFail(string $identifier): User
    {
        return $this->find($identifier)
            ?? throw AuthenticationException::userNotFound();
    }

    protected function findByUsername(string $username): ?User
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return User::query()
                ->whereRaw('lower(username) = lower(?)', [$username])
                ->first();
        }

        return User::query()
            ->whereRaw('lower(username) = lower(?)', [$username])
            ->first();
    }

    protected function normalizeRaw(string $identifier): string
    {
        return trim($identifier);
    }
}
