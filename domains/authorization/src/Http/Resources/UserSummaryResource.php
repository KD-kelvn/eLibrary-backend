<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Models\User;

/** @mixin User */
class UserSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => $this->whenLoaded('profile', fn () => [
                'fullname' => $this->profile?->fullname,
            ]),
        ];
    }
}
