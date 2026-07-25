<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menuItemId' => $this->menu_item_id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'actionType' => $this->action_type,
            'isActive' => $this->is_active,
            'menuItem' => NavigationMenuItemResource::make($this->whenLoaded('menuItem')),
            'roles' => RoleSummaryResource::collection($this->whenLoaded('roles')),
            'roleAssignments' => PermissionRoleAssignmentResource::collection(
                $this->whenLoaded('systemActionRoles'),
            ),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
