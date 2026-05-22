<?php

namespace Modules\Authentication\Services\Concerns;

use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Support\CredentialResolver;

trait ResolvesAuthenticatedUser
{
    protected function resolveUserByIdentifier(UserRepository $users, string $identifier): User
    {
        $type = CredentialResolver::detect($identifier);
        $user = $users->findByCredential($identifier, $type);

        if ($user === null) {
            throw AuthenticationException::userNotFound();
        }

        return $user;
    }

    protected function resolveUserForOneTimeTokenChannel(
        UserRepository $users,
        string $identifier,
        OneTimeTokenVia $via,
    ): User {
        $type = match ($via) {
            OneTimeTokenVia::Email => CredentialType::Email,
            OneTimeTokenVia::Phone => CredentialType::Phone,
        };

        $user = $users->findByCredential($identifier, $type);

        if ($user === null) {
            throw AuthenticationException::userNotFound();
        }

        $channelValue = $via === OneTimeTokenVia::Email ? $user->email : $user->phone;

        if ($channelValue === null || $channelValue === '') {
            throw AuthenticationException::unsupportedCredentialChannel($via->value);
        }

        return $user;
    }
}
