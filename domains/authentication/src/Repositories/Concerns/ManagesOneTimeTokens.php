<?php

namespace Modules\Authentication\Repositories\Concerns;

use Illuminate\Support\Collection;
use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Models\OneTimeToken;
use Modules\Authentication\Models\User;

trait ManagesOneTimeTokens
{
    public function revokeActiveForUser(User $user, OneTimeTokenPurpose $purpose): void
    {
        OneTimeToken::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('used_at')
            ->delete();
    }

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): OneTimeToken
    {
        return OneTimeToken::query()->create($attributes);
    }

    /** @return Collection<int, OneTimeToken> */
    public function activeForUser(User $user, OneTimeTokenPurpose $purpose): Collection
    {
        return OneTimeToken::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get();
    }

    public function markAsUsed(OneTimeToken $token): void
    {
        $token->update(['used_at' => now()]);
    }
}
