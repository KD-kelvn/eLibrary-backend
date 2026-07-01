<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\UserRole;

/**
 * @mixin UserRole
 */
class UserRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'details' => UserSummaryResource::make($this->whenLoaded('user'))
            ],
            'role' => [
                'id' => $this->role_id,
                'details' => RoleSummaryResource::make($this->whenLoaded('role'))
            ],
            'assignedBy' => [
                'id' => $this->assigned_by,
                'user' => UserSummaryResource::make($this->whenLoaded('assignedBy'))
            ],
            'expiresAt' => $this->expires_at?->toDateString(),
            'assignedAt' => $this->assigned_at?->toIso8601String(),
            'isActive' => $this->isActive(),
            'revocation' => RevokedRoleResource::make($this->whenLoaded('revokedRole')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
