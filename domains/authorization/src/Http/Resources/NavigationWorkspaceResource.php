<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight workspace payload for /auth/me and the post-login picker.
 */
class NavigationWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->code,
            'label' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon_code,
            'bgColor' => $this->bg_color,
            'landingUrl' => $this->landing_url,
            'badge' => $this->badge,
            'isPublic' => (bool) $this->is_public,
            'order' => (int) $this->sort_order,
        ];
    }
}
