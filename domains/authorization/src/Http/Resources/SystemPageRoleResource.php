<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\SystemPageRole;

/**
 * @mixin SystemPageRole
 */
class SystemPageRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'systemPageId' => $this->system_page_id,
            'roleId' => $this->role_id,
            'systemPage' => SystemPageResource::make($this->whenLoaded('systemPage')),
            'role' => RoleSummaryResource::make($this->whenLoaded('role')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
