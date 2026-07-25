<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionRoleAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roleId' => $this->role_id,
            'role' => RoleSummaryResource::make($this->whenLoaded('role')),
            'createdAt' => $this->created_at,
        ];
    }
}
