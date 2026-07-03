<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
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
            'rolesCount' => $this->whenCounted('systemModuleRoles'),
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'moduleRoles' => SystemModuleRoleResource::collection($this->whenLoaded('systemModuleRoles')),
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
