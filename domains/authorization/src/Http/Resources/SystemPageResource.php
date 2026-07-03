<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\SystemPage;

/**
 * @mixin SystemPage
 */
class SystemPageResource extends JsonResource
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
            'url' => $this->url,
            'isPublic' => $this->is_public,
            'rolesCount' => $this->whenCounted('systemPageRoles'),
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'pageRoles' => SystemPageRoleResource::collection($this->whenLoaded('systemPageRoles')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
