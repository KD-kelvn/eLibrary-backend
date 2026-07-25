<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\IssuesApiTokens;
use Modules\Authentication\Support\CredentialResolver;

class PasswordLoginService
{
    use IssuesApiTokens;

    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function login(array $data): array
    {
        $type = CredentialResolver::detect($data['identifier']);
        $user = $this->users->findByCredential($data['identifier'], $type);

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw AuthenticationException::invalidCredentials();
        }

        if ($user->is_blocked) {
            throw AuthenticationException::accountBlocked();
        }

        return $this->issueToken($user->load('profile'), $data['device_name'] ?? null);
    }
}
