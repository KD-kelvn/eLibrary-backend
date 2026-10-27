<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\Concerns\GuardsAccountAccess;
use Modules\Authentication\Services\Concerns\IssuesApiTokens;

class PasswordLoginService
{
    use GuardsAccountAccess;
    use IssuesApiTokens;

    public function __construct(
        private readonly UserRepository $users,
        private readonly IdentifierLookupService $identifiers,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function login(array $data): array
    {
        $user = $this->identifiers->find($data['identifier']);

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw AuthenticationException::invalidCredentials();
        }

        $this->assertCanAuthenticate($user);

        return $this->issueToken($user->load('profile'), $data['device_name'] ?? null);
    }
}
