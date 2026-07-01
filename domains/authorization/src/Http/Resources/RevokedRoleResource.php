<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\RevokedRole;

/**
 * @mixin RevokedRole
 */
class RevokedRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userRoleId' => $this->user_role_id,
            'revokedBy' => [
                'id' => $this->revoked_by,
                'user' => UserSummaryResource::make($this->whenLoaded('revokedBy'))
            ],
            'revokedAt' => $this->revoked_at?->toIso8601String(),
            'reason' => $this->reason,
            'assignment' => UserRoleResource::make($this->whenLoaded('userRole')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
