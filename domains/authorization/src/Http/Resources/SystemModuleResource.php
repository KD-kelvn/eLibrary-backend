<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Authorization\Models\SystemModule;

/**
 * @mixin SystemModule
 */
class SystemModuleResource extends JsonResource
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
            'code' => $this->code,
            'iconCode' => $this->icon_code,
            'bgColor' => $this->bg_color,
            'landingUrl' => $this->landing_url,
            'sortOrder' => (int) $this->sort_order,
            'isPublic' => (bool) $this->is_public,
            'isActive' => (bool) $this->is_active,
            'badge' => $this->badge,
            'rolesCount' => $this->whenCounted('systemModuleRoles'),
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'roleAssignments' => SystemModuleRoleResource::collection(
                $this->whenLoaded('systemModuleRoles'),
            ),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
