<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Http\Resources\UserRoleResource;

class ManagedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'isBlocked' => $this->is_blocked,
            'emailVerifiedAt' => $this->email_verified_at,
            'profile' => UserProfileResource::make($this->whenLoaded('profile')),
            'roleAssignments' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
