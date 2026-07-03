<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
                'color' => $this->status?->color(),
                'icon' => $this->status?->icon(),
            ],
            'code' => $this->code,
            'assignmentsCount' => $this->whenCounted('userRoles'),
            'activeAssignmentsCount' => $this->whenCounted('activeUserRoles'),
            'assignments' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'revocations' => RevokedRoleResource::collection($this->whenLoaded('revokedRoles')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
