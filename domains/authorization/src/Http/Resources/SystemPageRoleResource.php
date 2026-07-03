<?php

namespace Modules\Authorization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Authorization\Models\SystemPageRole;

/**
 * @mixin SystemPageRole
 */
class SystemPageRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'systemPageId' => $this->system_page_id,
            'roleId' => $this->role_id,
            'createdAt' => $this->whenNotNull($this->created_at),
            'updatedAt' => $this->whenNotNull($this->updated_at),
        ];
    }
}
