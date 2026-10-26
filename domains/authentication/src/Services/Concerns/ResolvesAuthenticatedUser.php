<?php

namespace Modules\Authentication\Services\Concerns;

use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Services\IdentifierLookupService;

trait ResolvesAuthenticatedUser
{
    protected function resolveUserByIdentifier(UserRepository $users, string $identifier): User
    {
        return app(IdentifierLookupService::class)->findOrFail($identifier);
    }

    protected function resolveUserForOneTimeTokenChannel(
        UserRepository $users,
        string $identifier,
        OneTimeTokenVia $via,
    ): User {
        $lookup = app(IdentifierLookupService::class);
        $type = match ($via) {
            OneTimeTokenVia::Email => CredentialType::Email,
            OneTimeTokenVia::Phone => CredentialType::Phone,
        };

        $normalized = $lookup->normalize($identifier, $type);
        $user = $users->findByCredential($normalized, $type);

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
