<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavigationMenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->code,
            'label' => $this->name,
            'route' => $this->route,
            'icon' => $this->icon_code,
            'isPublic' => $this->is_public,
            'order' => $this->sort_order,
        ];
    }
}
