<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menuId' => $this->menu_id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'route' => $this->route,
            'iconCode' => $this->icon_code,
            'sortOrder' => $this->sort_order,
            'isPublic' => $this->is_public,
            'isActive' => $this->is_active,
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'roleAssignments' => PermissionRoleAssignmentResource::collection(
                $this->whenLoaded('menuItemRoles'),
            ),
            'actions' => SystemActionResource::collection($this->whenLoaded('actions')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
