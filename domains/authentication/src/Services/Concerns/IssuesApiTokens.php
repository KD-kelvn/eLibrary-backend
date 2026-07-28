<?php

namespace Modules\Authentication\Services\Concerns;

use Modules\Authentication\Models\User;

trait IssuesApiTokens
{
    /**
     * @return array{user: User, access_token: string, token_type: string}
     */
    protected function issueToken(User $user, ?string $deviceName = null): array
    {
        $request = request();
        $name = filled($deviceName) ? $deviceName : $this->defaultDeviceName();

        $newToken = $user->createToken($name, ['*'], now()->addHours(1));

        $newToken->accessToken->forceFill([
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ])->save();

        return [
            'user' => $user,
            'access_token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    private function defaultDeviceName(): string
    {
        $agent = request()?->userAgent();

        if (! filled($agent)) {
            return 'Web session';
        }

        return 'Web · '.mb_substr($agent, 0, 80);
    }
}
