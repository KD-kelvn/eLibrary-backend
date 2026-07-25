<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'iconCode' => $this->icon_code,
            'sortOrder' => $this->sort_order,
            'isPublic' => $this->is_public,
            'isActive' => $this->is_active,
            'itemsCount' => $this->whenCounted('items'),
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'roleAssignments' => PermissionRoleAssignmentResource::collection(
                $this->whenLoaded('menuRoles'),
            ),
            'items' => MenuItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
