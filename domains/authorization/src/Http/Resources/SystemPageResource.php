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
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
