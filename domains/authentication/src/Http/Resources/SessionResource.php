<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Models\PersonalAccessToken;
use Modules\Authentication\Models\User;

/**
 * @mixin PersonalAccessToken
 */
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentId = $request->user()?->currentAccessToken()?->id;
        /** @var User|null $user */
        $user = $this->tokenable instanceof User ? $this->tokenable : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'lastUsedAt' => $this->last_used_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'isCurrent' => $currentId !== null && (int) $currentId === (int) $this->id,
            'isActive' => $this->isActive(),
            'isExpired' => $this->isExpired(),
            'user' => $user ? [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'fullname' => $user->profile?->fullname,
            ] : null,
        ];
    }
}
