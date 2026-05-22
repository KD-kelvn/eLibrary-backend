<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authentication\Models\UserProfile;

/** @mixin UserProfile */
class UserProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'fullname' => $this->fullname,
            'gender' => $this->gender,
            'dob' => $this->dob?->format('Y-m-d'),
            'profile_picture' => $this->profile_picture,
        ];
    }
}
