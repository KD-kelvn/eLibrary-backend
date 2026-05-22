<?php

namespace Modules\Authentication\Services\Concerns;

use Modules\Authentication\Models\User;

trait IssuesApiTokens
{
    /** @return array{user: User, access_token: string, token_type: string} */
    protected function issueToken(User $user, ?string $deviceName = null): array
    {
        $token = $user->createToken($deviceName ?? 'api-token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
