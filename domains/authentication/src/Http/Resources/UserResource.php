<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Models\User;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_blocked' => $this->is_blocked,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'profile' => UserProfileResource::make($this->whenLoaded('profile')),
        ];
    }
}
