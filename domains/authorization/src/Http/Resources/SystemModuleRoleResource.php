<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\SystemModuleRole;

/**
 * @mixin SystemModuleRole
 */
class SystemModuleRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'systemModuleId' => $this->system_module_id,
            'roleId' => $this->role_id,
            'systemModule' => SystemModuleResource::make($this->whenLoaded('systemModule')),
            'role' => RoleSummaryResource::make($this->whenLoaded('role')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
