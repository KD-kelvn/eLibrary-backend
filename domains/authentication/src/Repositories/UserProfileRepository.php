<?php

namespace Modules\Authentication\Repositories;

use Modules\Authentication\Models\UserProfile;

class UserProfileRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForUser(int $userId, array $attributes): UserProfile
    {
        return UserProfile::query()->create([
            'user_id' => $userId,
            ...$attributes,
        ]);
    }
}
