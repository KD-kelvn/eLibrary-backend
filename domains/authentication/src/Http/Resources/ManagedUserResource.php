<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Http\Resources\UserRoleResource;
use Modules\Authorization\Http\Resources\UserSystemActionResource;

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
            'isActive' => $this->is_active,
            'locale' => $this->locale ?? 'en',
            'lockedUntil' => $this->locked_until?->toIso8601String(),
            'breakGlass' => (bool) $this->break_glass,
            'deactivatedReason' => $this->deactivated_reason,
            'emailVerifiedAt' => $this->email_verified_at,
            'profile' => UserProfileResource::make($this->whenLoaded('profile')),
            'roleAssignments' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'actionGrants' => UserSystemActionResource::collection($this->whenLoaded('userSystemActions')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
